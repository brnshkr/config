<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

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
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(EventTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class EventTest
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
                [self::selectByClassnameSuffix('Event')],
                'Events',
                'Events',
            );

            yield PHPat::rule()
                ->classes(Selector::inNamespace($root . '\Events'))
                ->should()
                ->beFinal()
                ->because('Events must be final; they are immutable notifications.')
            ;

            yield PHPat::rule()
                ->classes(Selector::inNamespace($root . '\Events'))
                ->should()
                ->beReadonly()
                ->because('Events must be readonly; they are immutable domain notifications.')
            ;
        }
    }
}
