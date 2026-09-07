<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Tempest;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

/**
 * Forbid Tempest route handlers from depending on `Tempest\Database`.
 *
 * Classes under `<root>` carrying any of `#[Get]`, `#[Post]`, `#[Put]`, `#[Patch]`, `#[Delete]`
 * must go through a service or repository layer rather than touching the database directly —
 * keeps HTTP code separable from persistence concerns.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(RouteNoDatabaseTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class RouteNoDatabaseTest
{
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
            yield PHPat::rule()
                ->classes(Selector::AllOf(
                    Selector::inNamespace($root),
                    Selector::AnyOf(
                        Selector::appliesAttribute('Tempest\Http\Get'),
                        Selector::appliesAttribute('Tempest\Http\Post'),
                        Selector::appliesAttribute('Tempest\Http\Put'),
                        Selector::appliesAttribute('Tempest\Http\Patch'),
                        Selector::appliesAttribute('Tempest\Http\Delete'),
                    ),
                ))
                ->shouldNot()
                ->dependOn()
                ->classes(Selector::inNamespace('Tempest\Database'))
                ->because('Route handlers must not depend on Tempest\Database.')
            ;
        }
    }
}
