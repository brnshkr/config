<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Ddd;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class DomainEventImmutableTest
{
    /**
     * @param non-empty-string $domainEvent
     */
    public function __construct(
        private string $domainEvent,
    ) {}

    /**
     * @internal
     *
     * @return iterable<BuildStep>
     */
    #[TestRule]
    public function getRules(): iterable
    {
        yield PHPat::rule()
            ->classes(Selector::inNamespace($this->domainEvent))
            ->should()
            ->beFinal()
            ->because('Domain events must be final; they are sealed records of what happened.')
        ;

        yield PHPat::rule()
            ->classes(Selector::inNamespace($this->domainEvent))
            ->should()
            ->beReadonly()
            ->because('Domain events must be readonly; they are immutable records of past occurrences.')
        ;
    }
}
