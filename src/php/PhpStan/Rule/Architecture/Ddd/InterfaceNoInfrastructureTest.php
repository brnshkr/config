<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Ddd;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Forbid the Interface (presentation) layer from depending on the Infrastructure layer.
 *
 * Delivery code (controllers, CLI commands) must access infrastructure through Application
 * use cases, never reach for repositories or external clients directly.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(InterfaceNoInfrastructureTest::class, [
 *     'interface'      => 'Acme\Interface',
 *     'infrastructure' => 'Acme\Infrastructure',
 * ]);
 * ```
 */
final readonly class InterfaceNoInfrastructureTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $interface interface (presentation) layer namespace
     * @param non-empty-string $infrastructure infrastructure layer namespace
     */
    public function __construct(
        private string $interface,
        private string $infrastructure,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->interface,
            $this->infrastructure,
            'Interface must not depend on Infrastructure.',
        );
    }
}
