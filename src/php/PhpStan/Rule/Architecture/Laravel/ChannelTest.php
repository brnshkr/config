<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Laravel broadcasting channel placement and base class.
 *
 * Classes extending `Illuminate\Broadcasting\Channel` must live under `<root>\Broadcasting`,
 * and every class in that folder must extend the base channel.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ChannelTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class ChannelTest
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
            yield self::buildPlacementRule(
                $root,
                [Selector::extends('Illuminate\Broadcasting\Channel')],
                'Broadcasting',
                'Broadcasting channels',
            );

            yield self::buildMustExtendRule(
                Selector::inNamespace($root . '\Broadcasting'),
                'Illuminate\Broadcasting\Channel',
                'Broadcasting channels must extend Illuminate\Broadcasting\Channel.',
            );
        }
    }
}
