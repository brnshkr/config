<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\PhpStan\Rule\ResolvableDocReferenceRuleTest;
use Override;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VirtualNode;
use PHPStan\PhpDoc\Tag\ReturnTag;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\TrinaryLogic;
use PHPStan\Type\FileTypeMapper;

use function array_first;
use function explode;
use function in_array;
use function sprintf;

/**
 * Requires every `@see` target to name a symbol that exists, so a reference that no longer points
 * anywhere is reported instead of silently rotting.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/ResolvableDocReferenceRule.md
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<NodeAbstract>
 *
 * @see ResolvableDocReferenceRuleTest
 */
final readonly class ResolvableDocReferenceRule implements Rule
{
    use RuleTrait;

    private const string KEYWORD_SELF   = 'self';
    private const string KEYWORD_STATIC = 'static';

    /**
     * @internal invoked by PHPStan
     *
     * @param ReflectionProvider $reflectionProvider PHPStan reflection provider (auto-wired)
     * @param FileTypeMapper $fileTypeMapper PHPStan docblock resolver, applies the file's namespace and imports (auto-wired)
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private FileTypeMapper $fileTypeMapper,
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
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $doc = $node->getDocComment();

        if ($node instanceof VirtualNode || !$doc instanceof Doc) {
            return [];
        }

        $className = self::resolveSurroundingClassName($node, $scope);
        $errors    = [];

        foreach (explode("\n", $doc->getText()) as $offset => $line) {
            foreach (self::getReferenceTargets($line) as ['target' => $target, 'asLinkTag' => $asLinkTag]) {
                $error = $asLinkTag || self::isUriTarget($target)
                    ? self::processUriTarget($target, $doc->getStartLine() + $offset)
                    : $this->processTarget($target, $scope, $className, $doc->getStartLine() + $offset);

                if ($error instanceof IdentifierRuleError) {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }

    private static function resolveSurroundingClassName(Node $node, Scope $scope): ?string
    {
        return $scope->getClassReflection()?->getName()
            ?? ($node instanceof ClassLike ? $node->namespacedName?->toString() : null);
    }

    /**
     * @return list<array{
     *     target: non-empty-string,
     *     asLinkTag: bool,
     * }>
     */
    private static function getReferenceTargets(string $line): array
    {
        $targets = [];

        foreach (Str::matchAll($line, '/(?<opening>\{@|\*\s+@)(?<tag>link|see)\s+(?<target>[^\s}]+)/') as $match) {
            $target    = $match['target'] ?? '';
            $asLinkTag = ($match['tag'] ?? '') === 'link';

            if (Str::isEmpty($target)) {
                continue;
            }

            if ($asLinkTag
                || self::isUriTarget($target)
                || self::isReference($target, Str::startsWith($match['opening'] ?? '', '{'))) {
                $targets[] = [
                    'target'    => $target,
                    'asLinkTag' => $asLinkTag,
                ];
            }
        }

        return $targets;
    }

    private static function isReference(string $target, bool $asInlineTag): bool
    {
        if (self::isUriTarget($target)) {
            return false;
        }

        if ($asInlineTag || Str::contains($target, '::') || Str::contains($target, '\\')) {
            return true;
        }

        return Str::match($target, '/^\p{Lu}/') !== [];
    }

    private static function isUriTarget(string $target): bool
    {
        return Str::contains($target, '/');
    }

    private static function isAbsoluteUri(string $target): bool
    {
        return Str::contains($target, '://');
    }

    private function processTarget(string $target, Scope $scope, ?string $className, int $line): ?IdentifierRuleError
    {
        if (Str::contains($target, '::')) {
            return $this->processQualifiedTarget($target, $scope, $className, $line);
        }

        if (Str::endsWith($target, '()')) {
            return $this->processCallableTarget($target, $scope, $line);
        }

        if (Str::startsWith($target, '$')) {
            return null;
        }

        return $this->processSymbolTarget($target, $scope, $className, $line);
    }

    private static function processUriTarget(string $target, int $line): ?IdentifierRuleError
    {
        return self::isAbsoluteUri($target)
            ? null
            : self::buildRuleError(sprintf('Target `%s` must be an absolute URI.', $target), $line);
    }

    private function processQualifiedTarget(string $target, Scope $scope, ?string $className, int $line): ?IdentifierRuleError
    {
        $classPart    = Str::beforeLast($target, '::');
        $resolvedName = $this->resolveClassName($classPart, $scope, $className);

        if ($resolvedName === null || !$this->reflectionProvider->hasClass($resolvedName)) {
            return self::buildMissingError(self::KIND_CLASS, $resolvedName ?? $classPart, $line);
        }

        $member          = Str::afterLast($target, '::');
        $classReflection = $this->reflectionProvider->getClass($resolvedName);

        if (!self::hasMember($classReflection, $member)) {
            return self::buildMissingError(self::getKindForMember($member), $resolvedName . '::' . $member, $line);
        }

        return self::buildKeywordError($classReflection, $classPart, $member, $scope, $line);
    }

    private static function buildKeywordError(
        ClassReflection $classReflection,
        string $classPart,
        string $member,
        Scope $scope,
        int $line,
    ): ?IdentifierRuleError {
        $keyword = Str::toLowerCase($classPart);

        if (!in_array($keyword, [self::KEYWORD_SELF, self::KEYWORD_STATIC], true)) {
            return null;
        }

        $declaringClass = self::getDeclaringClass($classReflection, $member, $scope);

        if ($declaringClass !== $classReflection->getName()) {
            return self::buildRuleError(sprintf(
                '%s `%s` must be referenced by its declaring class, not `%s`.',
                self::getKindForMember($member),
                $declaringClass . '::' . $member,
                $keyword,
            ), $line);
        }

        if ($keyword === self::KEYWORD_SELF || self::isAbstractMember($classReflection, $member)) {
            return null;
        }

        return self::buildRuleError(sprintf(
            '%s `%s` must be referenced by `%s`, not `%s`.',
            self::getKindForMember($member),
            $declaringClass . '::' . $member,
            self::KEYWORD_SELF,
            self::KEYWORD_STATIC,
        ), $line);
    }

    private static function isAbstractMember(ClassReflection $classReflection, string $member): bool
    {
        if (self::getKindForMember($member) !== self::KIND_METHOD) {
            return false;
        }

        $isAbstract = $classReflection->getNativeMethod(Str::trimSuffix($member, '()'))->isAbstract();

        return $isAbstract instanceof TrinaryLogic ? $isAbstract->yes() : $isAbstract;
    }

    private static function getDeclaringClass(ClassReflection $classReflection, string $member, Scope $scope): string
    {
        $name = Str::trim(Str::trimSuffix($member, '()'), '$', 'start');

        return match (self::getKindForMember($member)) {
            self::KIND_METHOD   => $classReflection->getNativeMethod($name)->getDeclaringClass()->getName(),
            self::KIND_PROPERTY => self::getPropertyDeclaringClass($classReflection, $name, $scope)->getName(),
            default             => $classReflection->getConstant($name)->getDeclaringClass()->getName(),
        };
    }

    private static function getPropertyDeclaringClass(
        ClassReflection $classReflection,
        string $name,
        Scope $scope,
    ): ClassReflection {
        return $classReflection->hasInstanceProperty($name)
            ? $classReflection->getInstanceProperty($name, $scope)->getDeclaringClass()
            : $classReflection->getStaticProperty($name)->getDeclaringClass();
    }

    private function processCallableTarget(string $target, Scope $scope, int $line): ?IdentifierRuleError
    {
        return $this->hasGlobalSymbol(Str::trimSuffix($target, '()'), $scope, true)
            ? null
            : self::buildMissingError(self::KIND_FUNCTION, $target, $line);
    }

    private function processSymbolTarget(string $target, Scope $scope, ?string $className, int $line): ?IdentifierRuleError
    {
        $resolvedName = $this->resolveClassName($target, $scope, $className);

        if ($resolvedName !== null && $this->reflectionProvider->hasClass($resolvedName)) {
            return null;
        }

        if ($this->hasGlobalSymbol($target, $scope, false)) {
            return null;
        }

        return self::buildMissingError(self::KIND_CLASS, $resolvedName ?? $target, $line);
    }

    private function hasGlobalSymbol(string $name, Scope $scope, bool $asFunction): bool
    {
        $node = new Name(Str::trim($name, '\\', 'start'));

        return $asFunction
            ? $this->reflectionProvider->hasFunction($node, $scope)
            : $this->reflectionProvider->hasConstant($node, $scope);
    }

    /**
     * @param self::KIND_* $kind
     */
    private static function buildMissingError(string $kind, string $name, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s `%s` does not exist.',
            $kind,
            $name,
        ), $line);
    }

    /**
     * @return self::KIND_CONSTANT|self::KIND_METHOD|self::KIND_PROPERTY
     */
    private static function getKindForMember(string $member): string
    {
        return match (true) {
            Str::endsWith($member, '()')  => self::KIND_METHOD,
            Str::startsWith($member, '$') => self::KIND_PROPERTY,
            default                       => self::KIND_CONSTANT,
        };
    }

    private function resolveClassName(string $classPart, Scope $scope, ?string $className): ?string
    {
        if (Str::isEmpty($classPart)) {
            return $className;
        }

        $returnTag = $this->fileTypeMapper->getResolvedPhpDoc(
            fileName: $scope->getFile(),
            className: $className,
            traitName: null,
            functionName: null,
            docComment: '/** @return ' . $classPart . ' */',
        )->getReturnTag();

        return $returnTag instanceof ReturnTag
            ? array_first($returnTag->getType()->getObjectClassNames())
            : null;
    }

    private static function hasMember(ClassReflection $classReflection, string $member): bool
    {
        return match (self::getKindForMember($member)) {
            self::KIND_METHOD   => $classReflection->hasMethod(Str::trimSuffix($member, '()')),
            self::KIND_PROPERTY => self::hasProperty($classReflection, Str::trim($member, '$', 'start')),
            default             => $classReflection->hasConstant($member),
        };
    }

    private static function hasProperty(ClassReflection $classReflection, string $name): bool
    {
        if ($classReflection->hasInstanceProperty($name)) {
            return true;
        }

        return $classReflection->hasStaticProperty($name);
    }
}
