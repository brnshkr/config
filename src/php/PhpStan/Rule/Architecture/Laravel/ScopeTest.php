<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Laravel Eloquent scope placement and contract.
 *
 * Classes implementing `Illuminate\Database\Eloquent\Scope` must live under `<root>\Scopes`,
 * and every class in that folder must implement the contract.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ScopeTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class ScopeTest
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
            [Selector::implements('Illuminate\Database\Eloquent\Scope')],
            'Scopes',
            'Eloquent scopes',
        );

        yield self::buildMustImplementRule(
            Selector::inNamespace($this->root . '\Scopes'),
            'Illuminate\Database\Eloquent\Scope',
            'Eloquent scopes must implement Illuminate\Database\Eloquent\Scope.',
        );
    }
}
