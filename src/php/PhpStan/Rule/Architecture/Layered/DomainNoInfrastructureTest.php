<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Layered;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Forbid the Domain layer from depending on the Infrastructure layer.
 *
 * Domain types define business contracts; infrastructure implementations sit behind those
 * contracts. Inverting this dependency (domain reaching for concrete infrastructure) breaks
 * isolation and bloats the unit-test perimeter.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(DomainNoInfrastructureTest::class, [
 *     'domain'         => 'Acme\Domain',
 *     'infrastructure' => 'Acme\Infrastructure',
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class DomainNoInfrastructureTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $domain Domain layer namespace
     * @param non-empty-string $infrastructure Infrastructure layer namespace
     */
    public function __construct(
        private string $domain,
        private string $infrastructure,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->domain,
            $this->infrastructure,
            'Domain must not depend on Infrastructure.',
        );
    }
}
