<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * Enforce Symfony DI bundle/extension conventions.
 *
 * Four checks under `<root>\DependencyInjection`:
 *   - Classes named `*Extension` must extend `Symfony\Component\DependencyInjection\Extension\Extension`.
 *   - Classes named `*Extension` must be `final`.
 *   - Classes under `Compiler\` must implement `CompilerPassInterface`.
 *   - The single `Configuration` class must implement `ConfigurationInterface`.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(DependencyInjectionTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class DependencyInjectionTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-list<non-empty-string> $roots root namespaces, one per module
     */
    public function __construct(
        private array $roots,
    ) {}

    /**
     * @internal
     *
     * @return iterable<BuildStep>
     */
    #[TestRule]
    public function getRules(): iterable
    {
        foreach ($this->roots as $root) {
            $allOfSelectorModifier = Selector::AllOf(
                Selector::inNamespace($root . '\DependencyInjection'),
                self::selectByClassnameSuffix('Extension'),
            );

            yield self::buildMustExtendRule(
                $allOfSelectorModifier,
                Extension::class,
                'DI extension classes must extend Symfony\Component\DependencyInjection\Extension\Extension.',
            );

            yield PHPat::rule()
                ->classes($allOfSelectorModifier)
                ->should()
                ->beFinal()
                ->because('DI extension classes must be final.')
            ;

            yield self::buildMustImplementRule(
                Selector::inNamespace($root . '\DependencyInjection\Compiler'),
                CompilerPassInterface::class,
                'Compiler passes must implement Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface.',
            );

            yield self::buildMustImplementRule(
                Selector::classname($root . '\DependencyInjection\Configuration'),
                ConfigurationInterface::class,
                'DI Configuration must implement Symfony\Component\Config\Definition\ConfigurationInterface.',
            );
        }
    }
}
