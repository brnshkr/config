<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Override;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Const_ as ConstNode;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Const_ as ConstStmt;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Type\ClosureType;

use function array_filter;
use function array_map;
use function array_values;
use function sprintf;

/**
 * Requires every top-level declaration in a file to carry either an `@api` or an `@internal` tag.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/ApiOrInternalTagRule.md
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<NodeAbstract>
 */
final readonly class ApiOrInternalTagRule implements Rule
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
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $fileDoc = self::resolveFileLevelDoc($node, $scope);

        return array_values(array_filter(
            match (true) {
                $node instanceof ClassLike  => [self::processClassLike($node, $fileDoc)],
                $node instanceof Function_  => [self::processFunction($node, $fileDoc)],
                $node instanceof ConstStmt  => self::processGlobalConst($node, $fileDoc),
                $node instanceof Return_    => [self::processFileLevelReturn($node, $scope, $fileDoc)],
                $node instanceof Namespace_ => [self::processFileDoc($node, $fileDoc)],
                default                     => [],
            },
            static fn (?IdentifierRuleError $identifierRuleError): bool => $identifierRuleError instanceof IdentifierRuleError,
        ));
    }

    private static function processFileLevelReturn(Return_ $return, Scope $scope, ?Doc $fileDoc): ?IdentifierRuleError
    {
        if ($scope->isInClass()
            || $scope->getFunction() instanceof PhpFunctionFromParserNodeReflection
            || $scope->getAnonymousFunctionReflection() instanceof ClosureType) {
            return null;
        }

        if (self::hasConflictingVisibilityTags($return->getDocComment())) {
            return self::buildConflictError('Top-level `return`', $return->getStartLine());
        }

        if (self::getEffectiveVisibilityTag($return->getDocComment(), $fileDoc) !== null) {
            return null;
        }

        return self::buildRuleError(
            'Top-level `return` must carry either `@api` or `@internal` (either on the `return` statement or on the file).',
            $return->getStartLine(),
        );
    }

    private static function processFileDoc(Namespace_ $namespace, ?Doc $fileDoc): ?IdentifierRuleError
    {
        return self::hasConflictingVisibilityTags($fileDoc)
            ? self::buildConflictError('File-level docblock', $namespace->getStartLine())
            : null;
    }

    private static function processClassLike(ClassLike $classLike, ?Doc $fileDoc): ?IdentifierRuleError
    {
        if (self::isAnonymousClass($classLike)) {
            return null;
        }

        if (self::hasConflictingVisibilityTags($classLike->getDocComment())) {
            return self::buildConflictError(sprintf(
                '%s `%s`',
                self::getKindForClassLike($classLike),
                self::getClassLikeName($classLike),
            ), $classLike->getStartLine());
        }

        if (self::getEffectiveVisibilityTag($classLike->getDocComment(), $fileDoc) !== null) {
            return null;
        }

        return self::buildError(
            self::getKindForClassLike($classLike),
            self::getClassLikeName($classLike),
            $classLike->getStartLine(),
        );
    }

    private static function processFunction(Function_ $function, ?Doc $fileDoc): ?IdentifierRuleError
    {
        if (self::hasConflictingVisibilityTags($function->getDocComment())) {
            return self::buildConflictError(sprintf(
                '%s `%s`',
                self::KIND_FUNCTION,
                $function->name->toString(),
            ), $function->getStartLine());
        }

        return self::getEffectiveVisibilityTag($function->getDocComment(), $fileDoc) !== null
            ? null
            : self::buildError(self::KIND_FUNCTION, $function->name->toString(), $function->getStartLine());
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private static function processGlobalConst(ConstStmt $constStmt, ?Doc $fileDoc): array
    {
        if (self::hasConflictingVisibilityTags($constStmt->getDocComment())) {
            return array_values(array_map(
                static fn (ConstNode $constNode): IdentifierRuleError => self::buildConflictError(
                    sprintf('%s `%s`', self::KIND_CONSTANT, $constNode->name->toString()),
                    $constNode->getStartLine(),
                ),
                $constStmt->consts,
            ));
        }

        if (self::getEffectiveVisibilityTag($constStmt->getDocComment(), $fileDoc) !== null) {
            return [];
        }

        return array_values(array_map(
            static fn (ConstNode $constNode): IdentifierRuleError => self::buildError(
                self::KIND_CONSTANT,
                $constNode->name->toString(),
                $constNode->getStartLine(),
            ),
            $constStmt->consts,
        ));
    }

    private static function buildConflictError(string $subject, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s must carry exactly one visibility tag, but has both `@api` and `@internal`.',
            $subject,
        ), $line);
    }

    /**
     * @param self::KIND_* $kind
     */
    private static function buildError(string $kind, string $name, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s `%s` must carry either `@api` or `@internal`.',
            $kind,
            $name,
        ), $line);
    }
}
