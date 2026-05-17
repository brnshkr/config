<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Ddd;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

use function sprintf;

/**
 * Forbid the Domain layer from depending on any of a configured list of framework namespaces.
 *
 * Keeps the domain layer framework-agnostic so use-case logic survives framework upgrades and
 * stays independently testable. One isolation rule is generated per entry in `$isolatedFrom`.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(DomainNoFrameworkTest::class, [
 *     'domain'       => 'Acme\Domain',
 *     'isolatedFrom' => ['Doctrine\ORM', 'Symfony\Component\HttpFoundation'],
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class DomainNoFrameworkTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $domain Domain layer namespace
     * @param list<non-empty-string> $isolatedFrom Framework namespaces forbidden inside the domain layer
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
