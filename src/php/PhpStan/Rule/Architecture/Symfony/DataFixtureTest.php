<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Doctrine data-fixture placement and base class.
 *
 * Classes extending `Doctrine\Bundle\FixturesBundle\Fixture` must live under `<root>\DataFixtures`,
 * and every class in that folder must extend Fixture.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(DataFixtureTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class DataFixtureTest
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
            [Selector::extends('Doctrine\Bundle\FixturesBundle\Fixture')],
            'DataFixtures',
            'Data fixtures',
        );

        yield self::buildMustExtendRule(
            Selector::inNamespace($this->root . '\DataFixtures'),
            'Doctrine\Bundle\FixturesBundle\Fixture',
            'Data fixtures must extend Doctrine\Bundle\FixturesBundle\Fixture.',
        );
    }
}
