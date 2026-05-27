<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
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
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ResourceTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class ResourceTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root root application namespace
     */
    public function __construct(
        private string $root = Architecture::DEFAULT_ROOT,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildPlacementRule(
            $this->root,
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
