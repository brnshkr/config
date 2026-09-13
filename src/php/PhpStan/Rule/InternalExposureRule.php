<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Tests\PhpStan\Rule\InternalExposureRuleTest;
use Override;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\Tag\MethodTagParameter;
use PHPStan\PhpDoc\Tag\MixinTag;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;
use ReflectionMethod;
use ReflectionProperty;

use function array_diff;
use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function sprintf;

/**
 * Requires an `@api` symbol to name only `@api` types.
 *
 * Naming an `@internal` class hands a consumer a type the rules forbid them to use, and freezes that
 * class by accident: changing it becomes a breaking change for whoever read the signature.
 *
 * Types are read as the analysis resolves them, so a docblock type counts like a native one, and
 * `@throws` counts too. Inherited members and `@method`, `@property` and `@mixin` tags count the same
 * way. Extending an `@internal` class is not itself a leak — only the surface reaching the consumer
 * through the leaf is checked.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/InternalExposureRule.md
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<NodeAbstract>
 *
 * @see InternalExposureRuleTest
 */
final readonly class InternalExposureRule implements Rule
{
    use RuleTrait;

    /**
     * @internal invoked by PHPStan
     *
     * @param ReflectionProvider $reflectionProvider PHPStan reflection provider (auto-wired)
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {}

    /**
     * @internal invoked by PHPStan
     */
    #[Override]
    public function getNodeType(): string
    {
        return NodeAbstract::class;
    }

    /**
     * @internal invoked by PHPStan
     *
     * @return list<IdentifierRuleError>
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        return match (true) {
            $node instanceof ClassLike   => $this->processClassLike($node),
            $node instanceof Throw_      => $this->processThrow($node, $scope),
            $node instanceof ClassMethod => $this->processMethod($node, $scope),
            $node instanceof Property    => $this->processProperty($node, $scope),
            $node instanceof ClassConst  => $this->processClassConst($node, $scope),
            default                      => [],
        };
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processClassLike(ClassLike $classLike): array
    {
        $className = $classLike->namespacedName?->toString() ?? '';

        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (!self::hasClassTag($classReflection, self::TAG_API)) {
            return [];
        }

        return self::buildErrors(
            sprintf('%s `%s`', self::getKindForClassLike($classLike), $className),
            $this->keepInternal(array_merge(
                $this->getInheritedTypes($classReflection),
                self::getTaggedTypes($classReflection),
            )),
            $classLike->getStartLine(),
        );
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processMethod(ClassMethod $classMethod, Scope $scope): array
    {
        $classReflection = $this->resolvePublicApiClass($classMethod, $scope);
        $methodName      = $classMethod->name->toString();

        if (!$classReflection instanceof ClassReflection) {
            return [];
        }

        return self::buildErrors(
            sprintf('%s `%s()`', self::KIND_METHOD, $methodName),
            $this->keepInternal($this->getMethodTypes($classReflection, $methodName)),
            $classMethod->getStartLine(),
        );
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processProperty(Property $property, Scope $scope): array
    {
        $classReflection = $this->resolvePublicApiClass($property, $scope);

        if (!$classReflection instanceof ClassReflection) {
            return [];
        }

        $errors = [];

        foreach ($property->props as $propertyItem) {
            $propertyName = $propertyItem->name->toString();

            $errors = [...$errors, ...self::buildErrors(
                sprintf('%s `$%s`', self::KIND_PROPERTY, $propertyName),
                $this->keepInternal($this->getPropertyTypes($classReflection, $propertyName)),
                $propertyItem->getStartLine(),
            )];
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processClassConst(ClassConst $classConst, Scope $scope): array
    {
        $classReflection = $this->resolvePublicApiClass($classConst, $scope);

        if (!$classReflection instanceof ClassReflection) {
            return [];
        }

        $errors = [];

        foreach ($classConst->consts as $const) {
            $constantName = $const->name->toString();

            $errors = [...$errors, ...self::buildErrors(
                sprintf('%s `%s`', self::KIND_CONSTANT, $constantName),
                $this->keepInternal($this->getConstantTypes($classReflection, $constantName)),
                $const->getStartLine(),
            )];
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processThrow(Throw_ $throw, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        $methodName      = $scope->getFunctionName();

        if (!$classReflection instanceof ClassReflection || $methodName === null || !$classReflection->hasNativeMethod($methodName)) {
            return [];
        }

        $extendedMethodReflection = $classReflection->getNativeMethod($methodName);

        if (!$extendedMethodReflection->isPublic() || !self::isPublicApiMethod($extendedMethodReflection, $classReflection)) {
            return [];
        }

        $throwType  = $extendedMethodReflection->getThrowType();
        $documented = $throwType instanceof Type ? $this->keepInternal([$throwType]) : [];
        $thrown     = $this->keepInternal([$scope->getType($throw->expr)]);

        return self::buildErrors(
            sprintf('%s `%s()`', self::KIND_METHOD, $methodName),
            array_values(array_diff($thrown, $documented)),
            $throw->getStartLine(),
        );
    }

    private function resolvePublicApiClass(ClassConst|ClassMethod|Property $node, Scope $scope): ?ClassReflection
    {
        $classReflection = $scope->getClassReflection();

        if (!$classReflection instanceof ClassReflection || !$node->isPublic()) {
            return null;
        }

        $visibility = self::getEffectiveVisibilityTag($node->getDocComment(), self::resolveFileLevelDoc($node, $scope));

        return self::isPublicApiVisibility($visibility, $classReflection) ? $classReflection : null;
    }

    private static function isPublicApiMethod(ExtendedMethodReflection $extendedMethodReflection, ClassReflection $classReflection): bool
    {
        $phpDoc = $extendedMethodReflection->getResolvedPhpDoc()?->getPhpDocString();

        return self::isPublicApiVisibility(
            self::getEffectiveVisibilityTag($phpDoc === null ? null : new Doc($phpDoc), null),
            $classReflection,
        );
    }

    private static function isPublicApiVisibility(?string $visibility, ClassReflection $classReflection): bool
    {
        return $visibility !== self::TAG_INTERNAL
            && ($visibility === self::TAG_API || self::hasClassTag($classReflection, self::TAG_API));
    }

    /**
     * @param list<non-empty-string> $exposedNames
     *
     * @return list<IdentifierRuleError>
     */
    private static function buildErrors(string $subject, array $exposedNames, int $line): array
    {
        $errors = [];

        foreach ($exposedNames as $exposedName) {
            $errors[] = self::buildRuleError(sprintf(
                '%s is `@api` but names `%s`, which is `@internal`.',
                $subject,
                $exposedName,
            ), $line);
        }

        return $errors;
    }

    /**
     * @param list<Type> $types
     *
     * @return list<non-empty-string>
     */
    private function keepInternal(array $types): array
    {
        $exposedNames = [];

        foreach ($types as $type) {
            foreach ($type->getReferencedClasses() as $className) {
                if ($this->isInternalClass($className)) {
                    $exposedNames[] = $className;
                }
            }
        }

        return array_values(array_unique($exposedNames));
    }

    /**
     * @return list<Type>
     */
    private function getInheritedTypes(ClassReflection $classReflection): array
    {
        $types            = [];
        $nativeReflection = $classReflection->getNativeReflection();

        foreach ($nativeReflection->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            if ($this->isDeclaredInternallyElsewhere($classReflection, $reflectionMethod->getDeclaringClass()->getName())) {
                $types = [
                    ...$types,
                    ...$this->getMethodTypes($classReflection, $reflectionMethod->getName()),
                ];
            }
        }

        foreach ($nativeReflection->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            if ($this->isDeclaredInternallyElsewhere($classReflection, $reflectionProperty->getDeclaringClass()->getName())) {
                $types = [
                    ...$types,
                    ...$this->getPropertyTypes($classReflection, $reflectionProperty->getName()),
                ];
            }
        }

        return $types;
    }

    private function isDeclaredInternallyElsewhere(ClassReflection $classReflection, string $declaringClass): bool
    {
        return $declaringClass !== $classReflection->getName() && $this->isInternalClass($declaringClass);
    }

    /**
     * @return list<Type>
     */
    private static function getTaggedTypes(ClassReflection $classReflection): array
    {
        $types = array_values(array_map(
            static fn (MixinTag $mixinTag): Type => $mixinTag->getType(),
            $classReflection->getMixinTags(),
        ));

        foreach ($classReflection->getPropertyTags() as $propertyTag) {
            $types = [
                ...$types,
                ...array_values(array_filter([$propertyTag->getReadableType(), $propertyTag->getWritableType()])),
            ];
        }

        foreach ($classReflection->getMethodTags() as $methodTag) {
            $types = [
                $methodTag->getReturnType(),
                ...array_values(array_map(
                    static fn (MethodTagParameter $methodTagParameter): Type => $methodTagParameter->getType(),
                    $methodTag->getParameters(),
                )),
                ...$types,
            ];
        }

        return $types;
    }

    /**
     * @return list<Type>
     */
    private function getMethodTypes(ClassReflection $classReflection, string $methodName): array
    {
        if (!$classReflection->hasNativeMethod($methodName)) {
            return [];
        }

        $extendedMethodReflection = $classReflection->getNativeMethod($methodName);
        $variant                  = $extendedMethodReflection->getVariants()[0] ?? null;
        $throwType                = $extendedMethodReflection->getThrowType();

        return $variant === null ? [] : [
            $variant->getReturnType(),
            ...array_map(static fn (ParameterReflection $parameterReflection): Type => $parameterReflection->getType(), $variant->getParameters()),
            ...($throwType instanceof Type ? [$throwType] : []),
        ];
    }

    /**
     * @return list<Type>
     */
    private function getPropertyTypes(ClassReflection $classReflection, string $propertyName): array
    {
        return $classReflection->hasNativeProperty($propertyName)
            ? [$classReflection->getNativeProperty($propertyName)->getReadableType()]
            : [];
    }

    /**
     * @return list<Type>
     */
    private function getConstantTypes(ClassReflection $classReflection, string $constantName): array
    {
        return $classReflection->hasConstant($constantName)
            ? [$classReflection->getConstant($constantName)->getValueType()]
            : [];
    }

    private function isInternalClass(string $className): bool
    {
        return $this->reflectionProvider->hasClass($className)
            && self::hasClassTag($this->reflectionProvider->getClass($className), self::TAG_INTERNAL);
    }
}
