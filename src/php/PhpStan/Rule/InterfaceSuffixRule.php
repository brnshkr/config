<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use Override;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use RuntimeException;

use function array_filter;
use function array_values;
use function count;
use function sprintf;

/**
 * Requires classes that implement a single `<Prefix>Interface` to end with the matching `<Prefix>`.
 *
 * The intent is to make the contract-to-implementation pairing visible at the call site — a
 * `UserRepository` implementing `RepositoryInterface` reads better than a `UserRepo` would, and
 * keeps grep-able naming consistent across the codebase. The rule applies only when exactly one
 * `*Interface`-suffixed interface is implemented; classes with zero or multiple such interfaces
 * are skipped, since either situation makes the canonical suffix ambiguous.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/InterfaceSuffixRule.md
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<Class_>
 */
final readonly class InterfaceSuffixRule implements Rule
{
    use RuleTrait;

    /**
     * @internal invoked by PHPStan
     */
    #[Override]
    public function getNodeType(): string
    {
        return Class_::class;
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
        if ($node->isAnonymous() || $node->name === null) {
            return [];
        }

        $suffixed = array_values(array_filter(
            $node->implements,
            static fn (Name $name): bool => Str::endsWith(Str::getClassShortName($name->toString()), 'Interface'),
        ));

        if (count($suffixed) !== 1) {
            return [];
        }

        $className     = $node->name->toString();
        $interfaceName = Str::getClassShortName($suffixed[0]->toString());
        $expected      = Str::match($interfaceName, '/^(.+)Interface$/')[1] ?? '';

        if (Str::isEmpty($expected) || Str::endsWith($className, $expected)) {
            return [];
        }

        return [self::buildRuleError(sprintf(
            'Class `%s` implements `%s` and must end with suffix `%s`.',
            $className,
            $interfaceName,
            $expected,
        ), $node->getStartLine())];
    }
}
