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
 * Requires every top-level declaration to carry either an `@api` or an `@internal` tag.
 *
 * Applies to classes, traits, enums, interfaces, top-level functions, and global constants. The
 * intent is to make the public surface of a package a deliberate decision rather than an accident
 * of which symbols happened to be reachable. Anonymous classes are exempt.
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
        return array_values(array_filter(
            match (true) {
                $node instanceof ClassLike => [self::processClassLike($node)],
                $node instanceof Function_ => [self::processFunction($node)],
                $node instanceof ConstStmt => self::processGlobalConst($node),
                default                    => [],
            },
            static fn (?IdentifierRuleError $identifierRuleError): bool => $identifierRuleError instanceof IdentifierRuleError,
        ));
    }

    /**
     * @throws RuntimeException
     */
    private static function processClassLike(ClassLike $classLike): ?IdentifierRuleError
    {
        if (self::isAnonymousClass($classLike) || self::hasApiOrInternalTag($classLike->getDocComment())) {
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
    private static function processFunction(Function_ $function): ?IdentifierRuleError
    {
        return self::hasApiOrInternalTag($function->getDocComment())
            ? null
            : self::buildError(self::KIND_FUNCTION, $function->name->toString(), $function->getStartLine());
    }

    /**
     * @return list<IdentifierRuleError>
     *
     * @throws RuntimeException
     */
    private static function processGlobalConst(ConstStmt $constStmt): array
    {
        if (self::hasApiOrInternalTag($constStmt->getDocComment())) {
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

    private static function hasApiOrInternalTag(?Doc $doc): bool
    {
        if (self::hasTag($doc, 'api')) {
            return true;
        }

        return self::hasTag($doc, 'internal');
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
