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
 * @api
 *
 * @no-named-arguments
 */
final readonly class TwigExtensionTest
{
    use ArchitectureRuleTrait;

    /**
     * @param non-empty-string $root
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
