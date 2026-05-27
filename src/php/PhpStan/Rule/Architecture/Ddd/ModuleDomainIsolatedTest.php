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
 */
final readonly class ModuleDomainIsolatedTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $domain domain layer namespace
     * @param non-empty-string $module the module being isolated
     * @param list<non-empty-string> $siblings sibling module names within the same Domain namespace
     */
    public function __construct(
        private string $domain,
        private string $module,
        private array $siblings,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        $siblings = array_map(fn (string $sibling): string => $this->domain . '\\' . $sibling, $this->siblings);

        return PHPat::rule()
            ->classes(Selector::inNamespace($this->domain . '\\' . $this->module))
            ->shouldNot()
            ->dependOn()
            ->classes(...self::buildNamespaceSelectors($siblings))
            ->because(sprintf('%s must not depend on sibling modules.', $this->module))
        ;
    }
}
