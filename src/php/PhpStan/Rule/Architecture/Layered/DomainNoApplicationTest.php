<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Layered;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Forbid the Domain layer from depending on the Application layer.
 *
 * Domain model expresses business invariants and must remain ignorant of use-case
 * orchestration; the dependency arrow points inward, from Application into Domain.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(DomainNoApplicationTest::class, [
 *     'domain'      => 'Acme\Domain',
 *     'application' => 'Acme\Application',
 * ]);
 * ```
 */
final readonly class DomainNoApplicationTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $domain domain layer namespace
     * @param non-empty-string $application application layer namespace
     */
    public function __construct(
        private string $domain,
        private string $application,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->domain,
            $this->application,
            'Domain must not depend on Application.',
        );
    }
}
