<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Ddd;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Forbid the Interface (presentation) layer from depending on the Domain layer directly.
 *
 * Controllers and other presentation classes must go through Application use cases rather
 * than touching domain types directly — keeps domain refactors invisible to delivery code.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(InterfaceNoDomainTest::class, [
 *     'interface' => 'Acme\Interface',
 *     'domain'    => 'Acme\Domain',
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class InterfaceNoDomainTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $interface interface (presentation) layer namespace
     * @param non-empty-string $domain domain layer namespace
     */
    public function __construct(
        private string $interface,
        private string $domain,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->interface,
            $this->domain,
            'Interface must not depend on Domain.',
        );
    }
}
