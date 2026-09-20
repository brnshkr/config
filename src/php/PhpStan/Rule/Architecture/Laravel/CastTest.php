<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Laravel Eloquent cast placement and contract.
 *
 * Casts implementing `Illuminate\Contracts\Database\Eloquent\CastsAttributes` must live under
 * `<root>\Casts`, and every class in that folder must implement the contract — guarantees the
 * folder maps 1:1 to a single concept.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(CastTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class CastTest
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
                [Selector::implements('Illuminate\Contracts\Database\Eloquent\CastsAttributes')],
                'Casts',
                'Eloquent casts',
            );

            yield self::buildMustImplementRule(
                Selector::inNamespace($root . '\Casts'),
                'Illuminate\Contracts\Database\Eloquent\CastsAttributes',
                'Eloquent casts must implement Illuminate\Contracts\Database\Eloquent\CastsAttributes.',
            );
        }
    }
}
