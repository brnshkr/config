<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use Override;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use RuntimeException;

use function array_any;
use function array_filter;
use function array_map;
use function array_values;
use function implode;
use function is_string;
use function sprintf;

/**
 * Requires boolean-typed symbols (variables, parameters, properties, class constants, methods
 * returning `bool`) to start with one of the recognised boolish prefixes — see
 * {@see self::BOOLISH_PREFIXES}.
 *
 * The aim is that boolean-ness is obvious from the name alone, without needing to inspect the
 * type. Methods inherited from vendor interfaces/parents/traits are skipped since their names
 * are not the project's to change; magic methods other than `__construct` are skipped because
 * their names are fixed by the language.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<NodeAbstract>
 */
final readonly class BoolishPrefixRule implements Rule
{
    use RuleTrait;

    /**
     * NOTICE: Keep in sync with ts/naming-convention eslint rule.
     *
     * @internal
     */
    public const array BOOLISH_PREFIXES = [
        'as',
        'is',
        'does',
        'do',
        'did',
        'has',
        'was',
        'can',
    ];

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
     * @throws RuntimeException
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        return array_values(array_filter(
            match (true) {
                $node instanceof Function_   => self::processFunctionOrClassMethod($node, self::KIND_FUNCTION, $scope),
                $node instanceof ClassMethod => self::processFunctionOrClassMethod($node, self::KIND_METHOD, $scope),
                $node instanceof ClassConst  => self::processClassConst($node),
                $node instanceof Property    => self::processProperty($node),
                $node instanceof Assign      => [self::processAssign($node, $scope)],
                default                      => [],
            },
            static fn (?IdentifierRuleError $identifierRuleError): bool => $identifierRuleError instanceof IdentifierRuleError,
        ));
    }

    /**
     * @param self::KIND_FUNCTION|self::KIND_METHOD $kind
     *
     * @return list<?IdentifierRuleError>
     *
     * @throws RuntimeException
     */
    private static function processFunctionOrClassMethod(Function_|ClassMethod $functionOrMethod, string $kind, Scope $scope): array
    {
        return !$functionOrMethod instanceof ClassMethod || !self::isMethodLocked($functionOrMethod, $scope)
            ? [
                $functionOrMethod->returnType instanceof Identifier
                    && Str::toLowerCase($functionOrMethod->returnType->name) === 'bool'
                    && !self::hasBoolishPrefix($functionOrMethod->name->toString())
                    ? self::buildError($kind, $functionOrMethod->name->toString(), $functionOrMethod->getStartLine())
                    : null,
                ...array_values(array_map(self::processParam(...), $functionOrMethod->params)),
            ]
            : [];
    }

    /**
     * @return list<IdentifierRuleError>
     *
     * @throws RuntimeException
     */
    private static function processClassConst(ClassConst $classConst): array
    {
        return $classConst->type instanceof Identifier && Str::toLowerCase($classConst->type->name) === 'bool'
            ? array_map(
                static fn (Const_ $const): IdentifierRuleError => self::buildError(self::KIND_CONSTANT, $const->name->toString(), $const->getStartLine()),
                array_values(array_filter(
                    $classConst->consts,
                    static fn (Const_ $const): bool => !self::hasBoolishPrefix($const->name->toString()),
                )),
            )
            : [];
    }

    /**
     * @return list<IdentifierRuleError>
     *
     * @throws RuntimeException
     */
    private static function processProperty(Property $property): array
    {
        return $property->type instanceof Identifier && Str::toLowerCase($property->type->name) === 'bool'
            ? array_map(
                static fn (PropertyItem $propertyItem): IdentifierRuleError => self::buildError(self::KIND_PROPERTY, $propertyItem->name->toString(), $propertyItem->getStartLine()),
                array_values(array_filter(
                    $property->props,
                    static fn (PropertyItem $propertyItem): bool => !self::hasBoolishPrefix($propertyItem->name->toString()),
                )),
            )
            : [];
    }

    /**
     * @throws RuntimeException
     */
    private static function processParam(Param $param): ?IdentifierRuleError
    {
        return $param->type instanceof Identifier
            && Str::toLowerCase($param->type->name) === 'bool'
            && $param->var instanceof Variable
            && is_string($param->var->name)
            && !self::hasBoolishPrefix($param->var->name)
            ? self::buildError(
                $param->flags === 0 ? self::KIND_PARAMETER : self::KIND_PROPERTY,
                $param->var->name,
                $param->getStartLine(),
            )
            : null;
    }

    /**
     * @throws RuntimeException
     */
    private static function processAssign(Assign $assign, Scope $scope): ?IdentifierRuleError
    {
        return $assign->var instanceof Variable
            && is_string($assign->var->name)
            && $scope->getType($assign->expr)->isBoolean()->yes()
            && !$scope->hasVariableType($assign->var->name)->yes()
            && !self::hasBoolishPrefix($assign->var->name)
            ? self::buildError(self::KIND_VARIABLE, $assign->var->name, $assign->getStartLine())
            : null;
    }

    private static function isMethodLocked(ClassMethod $classMethod, Scope $scope): bool
    {
        $methodName = $classMethod->name->toString();

        if (Str::doesStartWith($methodName, '__') && $methodName !== '__construct') {
            return true;
        }

        $classReflection = $scope->getClassReflection();

        if (!$classReflection instanceof ClassReflection
            || $classReflection->isTrait()
            || $classReflection->isInterface()) {
            return false;
        }

        return $scope->getFile() === $classReflection->getFileName()
            && self::hasExternalUpstreamMethod($methodName, $classReflection);
    }

    private static function hasExternalUpstreamMethod(string $methodName, ClassReflection $classReflection): bool
    {
        $parentClass = $classReflection->getParentClass();

        while ($parentClass instanceof ClassReflection) {
            if ($parentClass->hasNativeMethod($methodName) && self::isExternal($parentClass)) {
                return true;
            }

            $parentClass = $parentClass->getParentClass();
        }

        foreach ($classReflection->getInterfaces() as $interface) {
            if ($interface->hasMethod($methodName) && self::isExternal($interface)) {
                return true;
            }
        }

        return array_any(
            $classReflection->getTraits(recursive: true),
            static fn (ClassReflection $classReflection): bool => $classReflection->hasNativeMethod($methodName) && self::isExternal($classReflection),
        );
    }

    private static function isExternal(ClassReflection $classReflection): bool
    {
        $fileName = $classReflection->getFileName();

        return $fileName === null || Str::doesContain($fileName, '/vendor/');
    }

    private static function hasBoolishPrefix(string $name): bool
    {
        return array_any(self::BOOLISH_PREFIXES, static fn (string $prefix): bool => Str::doesStartWith(Str::toLowerCase($name), $prefix));
    }

    /**
     * @param self::KIND_* $kind
     *
     * @throws RuntimeException
     */
    private static function buildError(string $kind, string $name, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s name `%s` must have one of the following prefixes: %s',
            $kind,
            $name,
            implode(', ', self::BOOLISH_PREFIXES),
        ), $line);
    }
}
