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
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use RuntimeException;

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
     *
     * @throws RuntimeException
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $fileDoc = self::resolveFileLevelDoc($node, $scope);

        return array_values(array_filter(
            match (true) {
                $node instanceof ClassLike => [self::processClassLike($node, $fileDoc)],
                $node instanceof Function_ => [self::processFunction($node, $fileDoc)],
                $node instanceof ConstStmt => self::processGlobalConst($node, $fileDoc),
                $node instanceof Return_   => [self::processFileLevelReturn($node, $scope, $fileDoc)],
                default                    => [],
            },
            static fn (?IdentifierRuleError $identifierRuleError): bool => $identifierRuleError instanceof IdentifierRuleError,
        ));
    }

    /**
     * @throws RuntimeException
     */
    private static function processFileLevelReturn(Return_ $return, Scope $scope, ?Doc $fileDoc): ?IdentifierRuleError
    {
        if ($scope->isInClass() || $scope->getFunction() !== null) {
            return null;
        }

        if (self::getEffectiveVisibilityTag($return->getDocComment(), $fileDoc) !== null) {
            return null;
        }

        return self::buildRuleError(
            'Top-level `return` must be annotated with either @internal or @api (either on the `return` statement or on the file).',
            $return->getStartLine(),
        );
    }

    /**
     * @throws RuntimeException
     */
    private static function processClassLike(ClassLike $classLike, ?Doc $fileDoc): ?IdentifierRuleError
    {
        if (self::isAnonymousClass($classLike) || self::getEffectiveVisibilityTag($classLike->getDocComment(), $fileDoc) !== null) {
            return null;
        }

        return self::buildError(
            self::getKindForClassLike($classLike),
            self::getClassLikeName($classLike),
            $classLike->getStartLine(),
        );
    }

    /**
     * @throws RuntimeException
     */
    private static function processFunction(Function_ $function, ?Doc $fileDoc): ?IdentifierRuleError
    {
        return self::getEffectiveVisibilityTag($function->getDocComment(), $fileDoc) !== null
            ? null
            : self::buildError(self::KIND_FUNCTION, $function->name->toString(), $function->getStartLine());
    }

    /**
     * @return list<IdentifierRuleError>
     *
     * @throws RuntimeException
     */
    private static function processGlobalConst(ConstStmt $constStmt, ?Doc $fileDoc): array
    {
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

    /**
     * @param self::KIND_* $kind
     *
     * @throws RuntimeException
     */
    private static function buildError(string $kind, string $name, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s `%s` must be annotated with either @internal or @api.',
            $kind,
            $name,
        ), $line);
    }
}
