<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Laravel API resource placement.
 *
 * Classes named `*Resource` extending either `JsonResource` or `ResourceCollection` must
 * live under `<root>\Http\Resources`.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ResourceTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class ResourceTest
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
                [
                    self::selectByClassnameSuffix('Resource'),
                    Selector::AnyOf(
                        Selector::extends('Illuminate\Http\Resources\Json\JsonResource'),
                        Selector::extends('Illuminate\Http\Resources\Json\ResourceCollection'),
                    ),
                ],
                'Http\Resources',
                'API resources',
            );
        }
    }
}
