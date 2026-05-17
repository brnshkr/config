<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use Override;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use RuntimeException;

use function in_array;
use function is_string;
use function sprintf;

/**
 * Holds every `@api` symbol to a consistent docblock standard.
 *
 * The intent is that anyone landing on a public class, function, or method gets the same level
 * of guidance regardless of where in the codebase they are: a real description in prose, one
 * `@param` per parameter with a sentence of context after the variable name, an `@return` line
 * that says what the value actually represents, and an `@example` block whenever calling the
 * symbol involves non-obvious arguments.
 *
 * A few exemptions keep the rule pragmatic: private methods and methods tagged `@internal` are
 * skipped entirely, constructors do not need their own description (the class docblock already
 * covers the type's purpose), fluent setters returning `self` or `static` skip the
 * `@return`/`@example` checks (the return type is self-explanatory), and interface/abstract
 * methods skip `@example` since they have no implementation to demonstrate. `@throws` coverage
 * is left to PHPStan's built-in throw-type checks.
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
     * @throws RuntimeException
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        return match (true) {
            $node instanceof ClassLike   => self::checkClassLike($node),
            $node instanceof Function_   => self::checkFunctionLike($node, $scope),
            $node instanceof ClassMethod => self::checkFunctionLike($node, $scope),
            default                      => [],
        };
    }

    /**
     * @return list<IdentifierRuleError>
     *
     * @throws RuntimeException
     */
    private static function checkClassLike(ClassLike $classLike): array
    {
        if (self::isAnonymousClass($classLike)) {
            return [];
        }

        $doc = $classLike->getDocComment();

        if (!self::hasTag($doc, 'api') || self::hasDescription($doc)) {
            return [];
        }

        return [self::buildDescriptionError(self::getKindForClassLike($classLike), self::getClassLikeName($classLike), $classLike->getStartLine())];
    }

    /**
     * @return list<IdentifierRuleError>
     *
     * @throws RuntimeException
     */
    private static function checkFunctionLike(ClassMethod|Function_ $node, Scope $scope): array
    {
        $classReflection = self::resolveApiContext($node, $scope);

        if ($classReflection === false) {
            return [];
        }

        $doc     = $node->getDocComment();
        $docText = $doc?->getText() ?? '';
        $kind    = self::getKindForFunctionLike($node);
        $name    = $node->name->toString();
        $line    = $node->getStartLine();
        $errors  = [];

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

        if (self::doesNeedReturnProse($node->returnType) && !self::hasReturnWithProse($docText)) {
            $errors[] = self::buildRuleError(sprintf(
                '%s `%s` is `@api` and returns a non-void type; an `@return` tag with a description is required.',
                $kind,
                $name,
            ), $line);
        }

        if (self::doesNeedExample($node, $classReflection) && !self::hasTagInText($docText, 'example')) {
            $errors[] = self::buildRuleError(sprintf(
                '%s `%s` is `@api` and accepts parameters; an `@example` tag is required.',
                $kind,
                $name,
            ), $line);
        }

        return $errors;
    }

    /**
     * Returns the containing class reflection for an `@api` method, null for an `@api` function,
     * or `false` when the symbol is out of scope.
     */
    private static function resolveApiContext(ClassMethod|Function_ $node, Scope $scope): ClassReflection|false|null
    {
        if ($node instanceof Function_) {
            return self::hasTag($node->getDocComment(), 'api') ? null : false;
        }

        if ($node->isPrivate()) {
            return false;
        }

        $class = $scope->getClassReflection();

        if (!$class instanceof ClassReflection) {
            return false;
        }

        $classDoc = $class->getNativeReflection()->getDocComment();

        if (!is_string($classDoc) || !self::hasTagInText($classDoc, 'api')) {
            return false;
        }

        if (self::hasTag($node->getDocComment(), 'internal')) {
            return false;
        }

        return $class;
    }

    private static function doesNeedReturnProse(Node|Identifier|null $returnType): bool
    {
        if ($returnType instanceof Identifier) {
            return !in_array(Str::toLowerCase($returnType->name), ['void', 'never'], true);
        }

        if ($returnType instanceof Name) {
            return !in_array(Str::toLowerCase($returnType->toString()), ['self', 'static'], true);
        }

        return $returnType instanceof Node;
    }

    private static function doesNeedExample(ClassMethod|Function_ $node, ?ClassReflection $classReflection): bool
    {
        if ($node->params === []) {
            return false;
        }

        if ($node instanceof ClassMethod && ($node->isAbstract() || $classReflection?->isInterface() === true)) {
            return false;
        }

        if (self::doesNeedReturnProse($node->returnType)) {
            return true;
        }

        return !$node->returnType instanceof Name;
    }

    private static function hasDescription(?Doc $doc): bool
    {
        $beforeTags = Str::match($doc?->getText() ?? '', '/\/\*\*(.*?)(?:\n[ \t]*\*[ \t]+@|\*\/)/s')[1] ?? '';

        return Str::match($beforeTags, '/^[ \t]*\*[ \t]+(?!@)[^\s*\/][^\n]*/m') !== [];
    }

    private static function hasParamProse(string $docText, string $paramName): bool
    {
        $after = Str::match($docText, sprintf('/@param\s+[^@]*?\$%s\b([^\n]*)/', $paramName))[1] ?? null;

        return $after !== null && Str::match($after, '/[A-Za-z]/') !== [];
    }

    private static function hasReturnWithProse(string $docText): bool
    {
        $after = Str::match($docText, '/@return\s+\S+\s+(\S[^\n]*)/')[1] ?? null;

        return $after !== null && Str::match($after, '/[A-Za-z]/') !== [];
    }

    /**
     * @param self::KIND_* $kind
     *
     * @throws RuntimeException
     */
    private static function buildDescriptionError(string $kind, string $name, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s `%s` is `@api` and must carry a description before the first doc-tag.',
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
