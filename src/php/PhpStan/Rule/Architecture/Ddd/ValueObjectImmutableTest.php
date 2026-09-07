<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Ddd;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

/**
 * Require all classes in the value-object namespace to be `final` and `readonly`.
 *
 * Value objects model identity-less domain concepts (Money, EmailAddress, Coordinate);
 * structural equality plus immutability is the whole contract.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ValueObjectImmutableTest::class, [
 *     'valueObject' => 'Acme\Domain\ValueObject',
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class ValueObjectImmutableTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $valueObject namespace containing the project's value objects
     */
    public function __construct(
        private string $valueObject,
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
            ->classes(self::selectInstantiableClassesIn($this->valueObject))
            ->should()
            ->beFinal()
            ->because('Value objects must be final; they cannot be extended.')
        ;

        yield PHPat::rule()
            ->classes(self::selectInstantiableClassesIn($this->valueObject))
            ->should()
            ->beReadonly()
            ->because('Value objects must be readonly; they are immutable by design.')
        ;
    }
}
