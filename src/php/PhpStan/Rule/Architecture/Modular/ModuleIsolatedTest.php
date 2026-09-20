<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Modular;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

use function sprintf;

/**
 * Forbid one module from depending on any of its sibling modules in a flat modular layout.
 *
 * Cross-module communication goes through explicit shared contracts rather than direct
 * namespace coupling.
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
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ModuleIsolatedTest::class, [
 *     'modules' => [
 *         ['module' => 'Acme\User', 'label' => 'User', 'siblings' => ['Acme\Email']],
 *         ['module' => 'Acme\Email', 'label' => 'Email', 'siblings' => ['Acme\User']],
 *     ],
 * ]);
 * ```
 */
final readonly class ModuleIsolatedTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-list<ModuleIsolation> $modules every module, with its siblings
     */
    public function __construct(
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

            yield self::buildNamespacesIsolationRule(
                $module['module'],
                $module['siblings'],
                sprintf('%s must not depend on sibling modules.', $module['label']),
            );
        }
    }
}
