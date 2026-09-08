<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use Override;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use ReflectionException;

use function in_array;
use function is_string;
use function sprintf;

/**
 * Holds every `@api` symbol to a consistent docblock standard.
 *
 * A description in prose, one `@param` per parameter, an `@return` that says what the value is,
 * and an `@example` wherever calling the symbol takes arguments. A file-level `@api` or
 * `@internal` sets the default for everything in the file, and a per-symbol tag overrides it.
 *
 * A top-level `return` in an `@api` file needs a description too, either on the `return` or on
 * the source it returns — a `new ClassName(...)` falls back to the class docblock, a
 * `Class::method(...)` to the method docblock.
 *
 * Private and `@internal` methods are exempt, and so is anything the return type or an ancestor
 * already explains.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/PublicApiDocumentationRule.md
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<NodeAbstract>
 */
final readonly class PublicApiDocumentationRule implements Rule
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
     *
     * @throws ReflectionException
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $fileDoc = self::resolveFileLevelDoc($node, $scope);

        return match (true) {
            $node instanceof ClassLike   => self::checkClassLike($node, $fileDoc),
            $node instanceof Function_   => self::checkFunctionLike($node, $scope, $fileDoc),
            $node instanceof ClassMethod => self::checkFunctionLike($node, $scope, $fileDoc),
            $node instanceof Return_     => $this->checkFileLevelReturn($node, $scope, $fileDoc),
            default                      => [],
        };
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private static function checkClassLike(ClassLike $classLike, ?Doc $fileDoc): array
    {
        if (self::isAnonymousClass($classLike)) {
            return [];
        }

        $doc = $classLike->getDocComment();

        if (self::getEffectiveVisibilityTag($doc, $fileDoc) !== self::TAG_API || self::hasDescription($doc)) {
            return [];
        }

        return [self::buildDescriptionError(self::getKindForClassLike($classLike), self::getClassLikeName($classLike), $classLike->getStartLine())];
    }

    /**
     * @return list<IdentifierRuleError>
     *
     * @throws ReflectionException
     */
    private static function checkFunctionLike(ClassMethod|Function_ $node, Scope $scope, ?Doc $fileDoc): array
    {
        $classReflection = self::resolveApiContext($node, $scope, $fileDoc);

        if ($classReflection === false) {
            return [];
        }

        $doc     = $node->getDocComment();
        $docText = $doc?->getText() ?? '';

        if ($node instanceof ClassMethod
            && (self::hasInheritDocTag($docText) || self::isDocumentedByAncestor($classReflection, $node))
        ) {
            return [];
        }

        $kind   = self::getKindForFunctionLike($node);
        $name   = $node->name->toString();
        $line   = $node->getStartLine();
        $errors = [];

        if ($kind !== self::KIND_CONSTRUCTOR && !self::hasDescription($doc)) {
            $errors[] = self::buildDescriptionError($kind, $name, $line);
        }

        foreach ($node->params as $param) {
            if (!$param->var instanceof Variable) {
                continue;
            }

            if (!is_string($param->var->name)) {
                continue;
            }

            if (!self::hasParamProse($docText, $param->var->name)) {
                $errors[] = self::buildRuleError(sprintf(
                    '%s `%s` is `@api`; parameter `$%s` must have an `@param` tag with a description.',
                    $kind,
                    $name,
                    $param->var->name,
                ), $line);
            }
        }

        if (self::needsReturnProse($node->returnType) && !self::hasReturnWithProse($docText)) {
            $errors[] = self::buildRuleError(sprintf(
                '%s `%s` is `@api` and returns a non-void type; an `@return` tag with a description is required.',
                $kind,
                $name,
            ), $line);
        }

        if (self::needsExample($node, $classReflection) && !self::hasTagInText($docText, 'example')) {
            $errors[] = self::buildRuleError(sprintf(
                '%s `%s` is `@api` and accepts parameters; an `@example` tag is required.',
                $kind,
                $name,
            ), $line);
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     *
     * @throws ReflectionException
     */
    private function checkFileLevelReturn(Return_ $return, Scope $scope, ?Doc $fileDoc): array
    {
        if ($scope->isInClass() || $scope->getFunction() instanceof PhpFunctionFromParserNodeReflection) {
            return [];
        }

        if (self::getVisibilityTag($fileDoc) !== self::TAG_API) {
            return [];
        }

        if (self::hasDescription($return->getDocComment())) {
            return [];
        }

        if ($this->hasReturnSourceWithDescription($return->expr)) {
            return [];
        }

        return [self::buildRuleError(
            'Top-level `return` in an `@api` file must carry a docblock with a description (either on the `return` statement or on its returned source).',
            $return->getStartLine(),
        )];
    }

    /**
     * @throws ReflectionException
     */
    private function hasReturnSourceWithDescription(?Expr $expr): bool
    {
        return self::hasDescription(match (true) {
            $expr instanceof New_                                            => $this->getReflectionDoc($expr->class),
            $expr instanceof StaticCall && $expr->name instanceof Identifier => $this->getReflectionDoc($expr->class, $expr->name),
            default                                                          => null,
        });
    }

    /**
     * @throws ReflectionException
     */
    private function getReflectionDoc(Node $classNode, ?Identifier $methodNode = null): ?Doc
    {
        $className = self::resolveClassName($classNode);

        if ($className === null || !$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (!$methodNode instanceof Identifier) {
            return self::wrapRawDoc($classReflection->getNativeReflection()->getDocComment());
        }

        $methodName = $methodNode->toString();

        if (!$classReflection->hasMethod($methodName)) {
            return null;
        }

        return self::wrapRawDoc($classReflection->getNativeReflection()->getMethod($methodName)->getDocComment());
    }

    private static function wrapRawDoc(string|false $rawDoc): ?Doc
    {
        return is_string($rawDoc) ? new Doc($rawDoc) : null;
    }

    private static function resolveClassName(Node $classNode): ?string
    {
        if (!$classNode instanceof Name) {
            return null;
        }

        $resolved = $classNode->getAttribute('resolvedName');

        return $resolved instanceof Name ? $resolved->toString() : $classNode->toString();
    }

    private static function resolveApiContext(ClassMethod|Function_ $node, Scope $scope, ?Doc $fileDoc): ClassReflection|false|null
    {
        if ($node instanceof Function_) {
            return self::getEffectiveVisibilityTag($node->getDocComment(), $fileDoc) === self::TAG_API ? null : false;
        }

        if ($node->isPrivate()) {
            return false;
        }

        $class = $scope->getClassReflection();

        if (!$class instanceof ClassReflection) {
            return false;
        }

        $classDoc          = $class->getNativeReflection()->getDocComment();
        $classDocObject    = is_string($classDoc) ? new Doc($classDoc) : null;
        $effectiveClassTag = self::getEffectiveVisibilityTag($classDocObject, $fileDoc);

        if ($effectiveClassTag !== self::TAG_API) {
            return false;
        }

        if (self::hasTag($node->getDocComment(), self::TAG_INTERNAL)) {
            return false;
        }

        return $class;
    }

    private static function hasInheritDocTag(string $docText): bool
    {
        return Str::match($docText, '/\*\s+@inheritDoc\b/i') !== [];
    }

    /**
     * @throws ReflectionException
     */
    private static function isDocumentedByAncestor(?ClassReflection $classReflection, ClassMethod $classMethod): bool
    {
        if (!$classReflection instanceof ClassReflection) {
            return false;
        }

        $methodName = $classMethod->name->toString();

        foreach ([...$classReflection->getParents(), ...$classReflection->getInterfaces()] as $ancestor) {
            if (!$ancestor->hasNativeMethod($methodName)) {
                continue;
            }

            if (self::hasDescription(self::wrapRawDoc(
                $ancestor->getNativeReflection()->getMethod($methodName)->getDocComment(),
            ))) {
                return true;
            }
        }

        return false;
    }

    private static function needsReturnProse(Node|Identifier|null $returnType): bool
    {
        if ($returnType instanceof Identifier) {
            return !in_array(Str::toLowerCase($returnType->name), ['void', 'never'], true);
        }

        if ($returnType instanceof Name) {
            return !in_array(Str::toLowerCase($returnType->toString()), ['self', 'static'], true);
        }

        return $returnType instanceof Node;
    }

    private static function needsExample(ClassMethod|Function_ $node, ?ClassReflection $classReflection): bool
    {
        if ($node->params === []) {
            return false;
        }

        if ($node instanceof ClassMethod && ($node->isAbstract() || $classReflection?->isInterface() === true)) {
            return false;
        }

        if (self::needsReturnProse($node->returnType)) {
            return true;
        }

        return !$node->returnType instanceof Name;
    }

    private static function hasDescription(?Doc $doc): bool
    {
        $beforeTags = Str::match($doc?->getText() ?? '', '/\/\*\*(?<body>.*?)(?:\n[\t ]*\*[\t ]+@|\*\/)/s')['body'] ?? '';

        return Str::match($beforeTags, '/^[\t ]*\*[\t ]+(?!@)[^\s*\/][^\n]*/m') !== [];
    }

    private static function hasParamProse(string $docText, string $paramName): bool
    {
        $after = Str::match($docText, sprintf('/@param\s[^@]*?\$%s\b(?<description>[^\n]*)/', $paramName))['description'] ?? null;

        return $after !== null && Str::match($after, '/[A-Za-z]/') !== [];
    }

    private static function hasReturnWithProse(string $docText): bool
    {
        $after = Str::match($docText, '/@return\s+\S+\s+(?<description>\S[^\n]*)/')['description'] ?? null;

        return $after !== null && Str::match($after, '/[A-Za-z]/') !== [];
    }

    /**
     * @param self::KIND_* $kind
     */
    private static function buildDescriptionError(string $kind, string $name, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s `%s` is `@api` and must carry a description before the first PHPDoc tag.',
            $kind,
            $name,
        ), $line);
    }

    /**
     * @return self::KIND_FUNCTION|self::KIND_CONSTRUCTOR|self::KIND_METHOD
     */
    private static function getKindForFunctionLike(ClassMethod|Function_ $node): string
    {
        if ($node instanceof Function_) {
            return self::KIND_FUNCTION;
        }

        return $node->name->toString() === '__construct' ? self::KIND_CONSTRUCTOR : self::KIND_METHOD;
    }
}
