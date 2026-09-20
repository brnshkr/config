<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Laravel Artisan command placement and base class.
 *
 * Classes named `*Command` or extending `Illuminate\Console\Command` must live under
 * `<root>\Console\Commands`, and every class in that folder must extend the base command.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(CommandTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class CommandTest
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
                [self::selectByClassnameSuffix('Command'), Selector::extends('Illuminate\Console\Command')],
                'Console\Commands',
                'Artisan commands',
            );

            yield self::buildMustExtendRule(
                Selector::inNamespace($root . '\Console\Commands'),
                'Illuminate\Console\Command',
                'Artisan commands must extend Illuminate\Console\Command.',
            );
        }
    }
}
