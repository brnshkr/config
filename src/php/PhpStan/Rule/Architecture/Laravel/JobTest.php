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
final readonly class JobTest
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
            [self::selectByClassnameSuffix('Job'), Selector::implements('Illuminate\Contracts\Queue\ShouldQueue')],
            'Jobs',
            'Queue jobs',
        );

        yield self::buildMustImplementRule(
            Selector::AllOf(
                Selector::inNamespace($this->root . '\Jobs'),
                self::selectByClassnameSuffix('Job'),
            ),
            'Illuminate\Contracts\Queue\ShouldQueue',
            'Queue jobs must implement Illuminate\Contracts\Queue\ShouldQueue.',
        );

        yield self::buildNamespaceIsolationRule(
            $this->root . '\Jobs',
            'Illuminate\Http',
            'Queue jobs must not depend on Illuminate\Http; jobs run outside the HTTP lifecycle.',
        );
    }
}
