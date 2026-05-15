<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Ddd;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

use function sprintf;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class DomainNoFrameworkTest
{
    use ArchitectureRuleTrait;

    /**
     * @param non-empty-string $domain
     * @param list<non-empty-string> $isolatedFrom
     */
    public function __construct(
        private string $domain,
        private array $isolatedFrom,
    ) {}

    /**
     * @internal
     *
     * @return iterable<BuildStep>
     */
    #[TestRule]
    public function getRules(): iterable
    {
        foreach ($this->isolatedFrom as $frameworkNamespace) {
            yield self::buildNamespaceIsolationRule(
                $this->domain,
                $frameworkNamespace,
                sprintf('Domain must not depend on %s; the domain layer must be framework-agnostic.', $frameworkNamespace),
            );
        }
    }
}
