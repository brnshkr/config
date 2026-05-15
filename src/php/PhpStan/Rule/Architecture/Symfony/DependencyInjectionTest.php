<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class DependencyInjectionTest
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
        $allOfSelectorModifier = Selector::AllOf(
            Selector::inNamespace($this->root . '\DependencyInjection'),
            self::selectByClassnameSuffix('Extension'),
        );

        yield self::buildMustExtendRule(
            $allOfSelectorModifier,
            'Symfony\Component\DependencyInjection\Extension\Extension',
            'DI extension classes must extend Symfony\Component\DependencyInjection\Extension\Extension.',
        );

        yield PHPat::rule()
            ->classes($allOfSelectorModifier)
            ->should()
            ->beFinal()
            ->because('DI extension classes must be final.')
        ;

        yield self::buildMustImplementRule(
            Selector::inNamespace($this->root . '\DependencyInjection\Compiler'),
            'Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface',
            'Compiler passes must implement Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface.',
        );

        yield self::buildMustImplementRule(
            Selector::classname($this->root . '\DependencyInjection\Configuration'),
            'Symfony\Component\Config\Definition\ConfigurationInterface',
            'DI Configuration must implement Symfony\Component\Config\Definition\ConfigurationInterface.',
        );
    }
}
