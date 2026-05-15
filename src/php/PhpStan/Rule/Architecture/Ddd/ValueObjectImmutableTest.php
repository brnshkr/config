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
final readonly class ValueObjectImmutableTest
{
    /**
     * @param non-empty-string $valueObject
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
            ->classes(Selector::inNamespace($this->valueObject))
            ->should()
            ->beFinal()
            ->because('Value objects must be final; they cannot be extended.')
        ;

        yield PHPat::rule()
            ->classes(Selector::inNamespace($this->valueObject))
            ->should()
            ->beReadonly()
            ->because('Value objects must be readonly; they are immutable by design.')
        ;
    }
}
