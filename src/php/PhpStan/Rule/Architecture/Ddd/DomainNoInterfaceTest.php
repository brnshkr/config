<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Ddd;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Forbid the Domain layer from depending on the Interface (presentation) layer.
 *
 * Domain logic must remain transport-agnostic: HTTP, CLI and other delivery mechanisms sit
 * outside the inward-pointing dependency arrow.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(DomainNoInterfaceTest::class, [
 *     'domain'    => 'Acme\Domain',
 *     'interface' => 'Acme\Interface',
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class DomainNoInterfaceTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $domain domain layer namespace
     * @param non-empty-string $interface interface (presentation) layer namespace
     */
    public function __construct(
        private string $domain,
        private string $interface,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->domain,
            $this->interface,
            'Domain must not depend on Interface.',
        );
    }
}
