<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Layered;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Forbid the Application layer from depending on the Infrastructure layer.
 *
 * Use cases orchestrate domain operations through abstractions (interfaces) rather than
 * concrete infrastructure: persistence, messaging, external clients sit behind ports the
 * application layer owns.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ApplicationNoInfrastructureTest::class, [
 *     'application'    => 'Acme\Application',
 *     'infrastructure' => 'Acme\Infrastructure',
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class ApplicationNoInfrastructureTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $application Application layer namespace
     * @param non-empty-string $infrastructure Infrastructure layer namespace
     */
    public function __construct(
        private string $application,
        private string $infrastructure,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->application,
            $this->infrastructure,
            'Application must not depend on Infrastructure.',
        );
    }
}
