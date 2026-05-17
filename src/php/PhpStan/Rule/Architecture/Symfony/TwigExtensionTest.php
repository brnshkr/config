<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use Twig\Extension\AbstractExtension;

/**
 * Enforce Twig extension placement and base class.
 *
 * Classes named `*Extension` extending `Twig\Extension\AbstractExtension` must live under
 * `<root>\Twig`, and every `*Extension` in that folder must extend AbstractExtension.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(TwigExtensionTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class TwigExtensionTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root Root application namespace
     */
    public function __construct(
        private string $root = Architecture::DEFAULT_ROOT,
    ) {}

    /**
     * @internal
     *
     * @return iterable<BuildStep>
     */
    #[TestRule]
    public function getRules(): iterable
    {
        yield self::buildPlacementRule(
            $this->root,
            [self::selectByClassnameSuffix('Extension'), Selector::extends(AbstractExtension::class)],
            'Twig',
            'Twig extensions',
        );

        yield self::buildMustExtendRule(
            Selector::AllOf(
                Selector::inNamespace($this->root . '\Twig'),
                self::selectByClassnameSuffix('Extension'),
            ),
            AbstractExtension::class,
            'Twig extensions must extend Twig\Extension\AbstractExtension.',
        );
    }
}
