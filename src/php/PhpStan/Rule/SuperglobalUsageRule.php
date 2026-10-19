<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Tests\PhpStan\Rule\SuperglobalUsageRuleTest;
use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

use function is_string;
use function sprintf;

/**
 * Reports code reading or writing a superglobal the configuration forbids.
 *
 * A superglobal is state any line can change, which no type and no signature reveals.
 * Symplify's `ForbiddenNodeRule` matches node classes, not variable names,
 * so this rule matches the name and reports the replacement configured for it.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<Variable>
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/SuperglobalUsageRule.md
 * @see SuperglobalUsageRuleTest
 *
 * @example
 * ```php
 * use Brnshkr\Config\PhpStan;
 *
 * PhpStan::getBuilder()
 *     ->replaceRule(SuperglobalUsageRule::class, [
 *         'forbiddenSuperglobals' => [
 *             '$_SERVER' => 'Read the environment in one place and inject the value instead.',
 *         ],
 *     ])
 * ;
 * ```
 */
final readonly class SuperglobalUsageRule implements Rule
{
    use RuleTrait;

    /**
     * @internal invoked by PHPStan
     *
     * @param array<string, string> $forbiddenSuperglobals - what to use instead, keyed by the superglobal
     */
    public function __construct(
        private array $forbiddenSuperglobals = [],
    ) {}

    /**
     * @internal invoked by PHPStan
     */
    #[Override]
    public function getNodeType(): string
    {
        return Variable::class;
    }

    /**
     * @internal invoked by PHPStan
     *
     * @return list<IdentifierRuleError>
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (!is_string($node->name)) {
            return [];
        }

        $superglobal = '$' . $node->name;
        $replacement = $this->forbiddenSuperglobals[$superglobal] ?? null;

        if ($replacement === null) {
            return [];
        }

        return [self::buildRuleError(
            sprintf('Superglobal `%s` must not be used. %s', $superglobal, $replacement),
            $node->getStartLine(),
        )];
    }
}
