<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

/**
 * Enforce Laravel event placement and immutability.
 *
 * Classes named `*Event` must live under `<root>\Events`, and every class in that folder
 * must be `final` and `readonly` — events are sealed, immutable notifications.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(EventTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class EventTest
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
            [self::selectByClassnameSuffix('Event')],
            'Events',
            'Events',
        );

        yield PHPat::rule()
            ->classes(Selector::inNamespace($this->root . '\Events'))
            ->should()
            ->beFinal()
            ->because('Events must be final; they are immutable notifications.')
        ;

        yield PHPat::rule()
            ->classes(Selector::inNamespace($this->root . '\Events'))
            ->should()
            ->beReadonly()
            ->because('Events must be readonly; they are immutable domain notifications.')
        ;
    }
}
