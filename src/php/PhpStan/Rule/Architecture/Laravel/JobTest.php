<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Laravel queue job placement, contract and HTTP isolation.
 *
 * Classes named `*Job` or implementing `Illuminate\Contracts\Queue\ShouldQueue` must live
 * under `<root>\Jobs`, every `*Job` in that folder must implement `ShouldQueue`, and jobs
 * may not depend on `Illuminate\Http` — they run outside the HTTP request lifecycle.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(JobTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class JobTest
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
