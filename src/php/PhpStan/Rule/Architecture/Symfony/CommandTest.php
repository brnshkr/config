<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use Symfony\Component\Console\Command\Command;

/**
 * Enforce Symfony Console command placement, base class and HTTP isolation.
 *
 * Classes named `*Command` must live under `<root>\Command`, every class in that folder must
 * extend `Symfony\Component\Console\Command\Command`, and commands may not depend on
 * `Symfony\Component\HttpFoundation` — console commands run outside the HTTP lifecycle.
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
                [self::selectByClassnameSuffix('Command')],
                'Command',
                'Console commands',
            );

            yield self::buildMustExtendRule(
                Selector::inNamespace($root . '\Command'),
                Command::class,
                'Console commands must extend Symfony\Component\Console\Command\Command.',
            );

            yield self::buildNamespaceIsolationRule(
                $root . '\Command',
                'Symfony\Component\HttpFoundation',
                'Console commands must not depend on Symfony\Component\HttpFoundation.',
            );
        }
    }
}
