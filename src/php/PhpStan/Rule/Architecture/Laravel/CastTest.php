<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class CastTest
{
    use ArchitectureRuleTrait;

    /**
     * @param non-empty-string $root
     */
    public function __construct(
        private string $root = Architecture::DEFAULT_ROOT,
    ) {}

    /**
     * @internal
     *
     * @return iterable<BuildStep>
     */
    #[TestRule]
    public function getRules(): iterable
    {
        yield self::buildPlacementRule(
            $this->root,
            [Selector::implements('Illuminate\Contracts\Database\Eloquent\CastsAttributes')],
            'Casts',
            'Eloquent casts',
        );

        yield self::buildMustImplementRule(
            Selector::inNamespace($this->root . '\Casts'),
            'Illuminate\Contracts\Database\Eloquent\CastsAttributes',
            'Eloquent casts must implement Illuminate\Contracts\Database\Eloquent\CastsAttributes.',
        );
    }
}
