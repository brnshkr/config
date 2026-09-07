<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

use function array_filter;
use function sprintf;

/**
 * Requires every public-facing declaration that exposes parameters to declare a named-arguments stance.
 *
 * PHP's named-argument syntax turns parameter names into part of the contract the moment a caller
 * writes `someFunction(name: 'foo')`, and the stance says whether that is allowed.
 * `@named-arguments` is the deliberate claim that the names are the contract;
 * `@no-named-arguments` keeps them out of it, leaving them free to be renamed.
 *
 * A class-level stance is required only where a method would otherwise be left ungoverned.
 * Parameterless declarations, `@internal` symbols and anonymous classes are exempt.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/NamedArgumentsTagRule.md
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<NodeAbstract>
 */
final readonly class NamedArgumentsTagRule implements Rule
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
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        return array_filter(
            match (true) {
                $node instanceof ClassLike => [self::processClassLike($node)],
                $node instanceof Function_ => [self::processFunction($node)],
                default                    => [],
            },
            static fn (?IdentifierRuleError $identifierRuleError): bool => $identifierRuleError instanceof IdentifierRuleError,
        );
    }

    private static function processClassLike(ClassLike $classLike): ?IdentifierRuleError
    {
        if (self::isAnonymousClass($classLike) || !self::hasUngovernedMethod($classLike, self::hasNamedArgumentsStance(...))) {
            return null;
        }

        $doc = $classLike->getDocComment();

        if (self::hasNamedArgumentsStance($doc)) {
            return null;
        }

        return self::buildError(
            self::getKindForClassLike($classLike),
            self::getClassLikeName($classLike),
            $classLike->getStartLine(),
        );
    }

    private static function processFunction(Function_ $function): ?IdentifierRuleError
    {
        if ($function->getParams() === []) {
            return null;
        }

        $doc = $function->getDocComment();

        if (self::hasNamedArgumentsStance($doc)) {
            return null;
        }

        return self::buildError(self::KIND_FUNCTION, $function->name->toString(), $function->getStartLine());
    }

    /**
     * @param self::KIND_* $kind
     */
    private static function buildError(string $kind, string $name, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s `%s` must carry either `@named-arguments` or `@no-named-arguments`.',
            $kind,
            $name,
        ), $line);
    }
}
