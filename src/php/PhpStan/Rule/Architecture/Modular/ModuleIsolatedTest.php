<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Modular;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

use function sprintf;

/**
 * Forbid one module from depending on any of its sibling modules in a flat modular layout.
 *
 * Unlike the layered DDD variants, `$module` here is the fully-qualified module namespace
 * (no domain/application split). Used by {@see Architecture::modular()}
 * to generate per-module isolation rules from a list of module names plus a placeholder pattern.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ModuleIsolatedTest::class, [
 *     'module'   => 'Acme\User',
 *     'label'    => 'User',
 *     'siblings' => ['Acme\Email'],
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
     * @param non-empty-string $module fully-qualified module namespace being isolated
     * @param non-empty-string $label short module label used in the violation message
     * @param list<non-empty-string> $siblings fully-qualified namespaces of sibling modules
     */
    public function __construct(
        private string $module,
        private string $label,
        private array $siblings,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace($this->module))
            ->shouldNot()
            ->dependOn()
            ->classes(...self::buildNamespaceSelectors($this->siblings))
            ->because(sprintf('%s must not depend on sibling modules.', $this->label))
        ;
    }
}
