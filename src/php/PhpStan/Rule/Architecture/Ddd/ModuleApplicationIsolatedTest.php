<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Ddd;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

use function array_map;
use function sprintf;

/**
 * Forbid one Application-layer module from depending on any of its sibling modules.
 *
 * Module isolation forces cross-module communication through explicit shared kernels or
 * domain events; siblings stay independently replaceable.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ModuleApplicationIsolatedTest::class, [
 *     'application' => 'Acme\Application',
 *     'module'      => 'User',
 *     'siblings'    => ['Email'],
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class ModuleApplicationIsolatedTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $application application layer namespace
     * @param non-empty-string $module the module being isolated
     * @param list<non-empty-string> $siblings sibling module names within the same Application namespace
     */
    public function __construct(
        private string $application,
        private string $module,
        private array $siblings,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        $siblings = array_map(fn (string $sibling): string => $this->application . '\\' . $sibling, $this->siblings);

        return PHPat::rule()
            ->classes(Selector::inNamespace($this->application . '\\' . $this->module))
            ->shouldNot()
            ->dependOn()
            ->classes(...self::buildNamespaceSelectors($siblings))
            ->because(sprintf('%s must not depend on sibling modules.', $this->module))
        ;
    }
}
