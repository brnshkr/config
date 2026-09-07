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
 *
 * @phpstan-type ModuleIsolation array{
 *     module: non-empty-string,
 *     label: non-empty-string,
 *     siblings: list<non-empty-string>,
 * }
 */
final readonly class ModuleIsolatedTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root root application namespace
     * @param non-empty-list<ModuleIsolation> $modules every module, with its siblings
     */
    public function __construct(
        private string $root,
        private array $modules,
    ) {}

    /**
     * @internal
     *
     * @return iterable<BuildStep>
     */
    #[TestRule]
    public function getRules(): iterable
    {
        foreach ($this->modules as $module) {
            if ($module['siblings'] === []) {
                continue;
            }

            $siblings = array_map(fn (string $sibling): string => $this->root . '\\' . $sibling, $module['siblings']);

            yield PHPat::rule()
                ->classes(Selector::inNamespace($this->root . '\\' . $module['module']))
                ->shouldNot()
                ->dependOn()
                ->classes(...self::buildNamespaceSelectors($siblings))
                ->because(sprintf('%s must not depend on sibling modules.', $module['label']))
            ;
        }
    }
}
