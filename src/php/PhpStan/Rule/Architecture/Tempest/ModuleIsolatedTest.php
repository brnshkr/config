<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Tempest;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

use function array_map;
use function sprintf;

/**
 * Forbid one Tempest module from depending on any of its sibling modules.
 *
 * Module isolation in Tempest's flat module layout: cross-module communication must go
 * through explicit shared contracts rather than direct namespace coupling.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ModuleIsolatedTest::class, [
 *     'root'     => 'Acme',
 *     'module'   => 'User',
 *     'siblings' => ['Email'],
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class ModuleIsolatedTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root root application namespace
     * @param non-empty-string $module the module being isolated (relative to `$root`)
     * @param list<non-empty-string> $siblings sibling module names (relative to `$root`)
     */
    public function __construct(
        private string $root,
        private string $module,
        private array $siblings,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        $siblings = array_map(fn (string $sibling): string => $this->root . '\\' . $sibling, $this->siblings);

        return PHPat::rule()
            ->classes(Selector::inNamespace($this->root . '\\' . $this->module))
            ->shouldNot()
            ->dependOn()
            ->classes(...self::buildNamespaceSelectors($siblings))
            ->because(sprintf('%s must not depend on sibling modules.', $this->module))
        ;
    }
}
