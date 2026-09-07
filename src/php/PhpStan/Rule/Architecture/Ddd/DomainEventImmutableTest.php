<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Ddd;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
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
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $domainEvent namespace containing the project's domain event classes
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
            ->classes(self::selectInstantiableClassesIn($this->domainEvent))
            ->should()
            ->beFinal()
            ->because('Domain events must be final; they are sealed records of what happened.')
        ;

        yield PHPat::rule()
            ->classes(self::selectInstantiableClassesIn($this->domainEvent))
            ->should()
            ->beReadonly()
            ->because('Domain events must be readonly; they are immutable records of past occurrences.')
        ;
    }
}
