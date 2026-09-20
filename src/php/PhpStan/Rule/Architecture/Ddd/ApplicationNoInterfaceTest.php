<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Ddd;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Forbids the Application layer from depending on the Interface (presentation) layer.
 *
 * In a DDD layout the dependency arrow points inward — the Application layer orchestrates use
 * cases and may not know how those use cases are delivered.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ApplicationNoInterfaceTest::class, [
 *     'application' => 'Acme\Application',
 *     'interface'   => 'Acme\Interface',
 * ]);
 * ```
 */
final readonly class ApplicationNoInterfaceTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $application application layer namespace
     * @param non-empty-string $interface interface (presentation) layer namespace
     */
    public function __construct(
        private string $application,
        private string $interface,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->application,
            $this->interface,
            'Application must not depend on Interface.',
        );
    }
}
