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
 * Forbid one Domain-layer module from depending on any of its sibling modules.
 *
 * Module isolation in the domain layer prevents cross-module entanglement at the model
 * level; integration belongs in the application or interface layer.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ModuleDomainIsolatedTest::class, [
 *     'domain'   => 'Acme\Domain',
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
final readonly class ModuleDomainIsolatedTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $domain domain layer namespace
     * @param non-empty-list<ModuleIsolation> $modules every module, with its siblings
     */
    public function __construct(
        private string $domain,
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

            $siblings = array_map(fn (string $sibling): string => $this->domain . '\\' . $sibling, $module['siblings']);

            yield PHPat::rule()
                ->classes(Selector::inNamespace($this->domain . '\\' . $module['module']))
                ->shouldNot()
                ->dependOn()
                ->classes(...self::buildNamespaceSelectors($siblings))
                ->because(sprintf('%s must not depend on sibling modules.', $module['label']))
            ;
        }
    }
}
