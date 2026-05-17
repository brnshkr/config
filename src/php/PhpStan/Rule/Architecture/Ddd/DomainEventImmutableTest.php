<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Ddd;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

/**
 * Require all classes in the domain-event namespace to be `final` and `readonly`.
 *
 * Domain events represent sealed, immutable records of past occurrences; they must not be
 * subclassable or mutable after construction.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(DomainEventImmutableTest::class, [
 *     'domainEvent' => 'Acme\Domain\Event',
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class DomainEventImmutableTest
{
    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $domainEvent Namespace containing the project's domain event classes
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
