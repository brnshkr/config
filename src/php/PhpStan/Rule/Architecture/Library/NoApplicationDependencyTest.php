<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Library;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Forbid a package from depending on the namespace of the application that installs it.
 *
 * The application is the consumer, so a package naming it inverts the dependency and works in
 * exactly one project.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(NoApplicationDependencyTest::class, [
 *     'root'        => 'Acme\Bundle',
 *     'application' => 'App',
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class NoApplicationDependencyTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root package namespace
     * @param non-empty-string $application namespace of the installing application
     */
    public function __construct(
        private string $root,
        private string $application,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->root,
            $this->application,
            'A package must not depend on the application that installs it.',
        );
    }
}
