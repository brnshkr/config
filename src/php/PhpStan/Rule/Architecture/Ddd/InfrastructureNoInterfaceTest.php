<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Ddd;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Forbid the Infrastructure layer from depending on the Interface (presentation) layer.
 *
 * Infrastructure (persistence, messaging, external APIs) is a horizontal concern shared by
 * Domain and Application; it must not be coupled to delivery mechanisms.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(InfrastructureNoInterfaceTest::class, [
 *     'infrastructure' => 'Acme\Infrastructure',
 *     'interface'      => 'Acme\Interface',
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class InfrastructureNoInterfaceTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $infrastructure infrastructure layer namespace
     * @param non-empty-string $interface interface (presentation) layer namespace
     */
    public function __construct(
        private string $infrastructure,
        private string $interface,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->infrastructure,
            $this->interface,
            'Infrastructure must not depend on Interface.',
        );
    }
}
