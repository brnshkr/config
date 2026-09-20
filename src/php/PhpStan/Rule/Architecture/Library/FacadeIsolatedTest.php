<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Library;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

use function sprintf;

/**
 * Forbid the implementations behind a facade from reaching back through it.
 *
 * A facade exists so a consumer never names the classes it builds. One of those classes calling
 * the facade routes an internal dependency through the public surface, which makes the surface
 * impossible to change without changing the implementation too.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(FacadeIsolatedTest::class, [
 *     'facade'    => 'Acme\Filter',
 *     'namespace' => 'Acme\Filter',
 *     'label'     => 'Filter',
 * ]);
 * ```
 */
final readonly class FacadeIsolatedTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $facade fully-qualified facade class
     * @param non-empty-string $namespace namespace holding what the facade builds
     * @param non-empty-string $label short label used in the violation message
     */
    public function __construct(
        private string $facade,
        private string $namespace,
        private string $label,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildClassIsolationRule(
            $this->namespace,
            $this->facade,
            sprintf('%s implementations must not depend on the facade that builds them.', $this->label),
        );
    }
}
