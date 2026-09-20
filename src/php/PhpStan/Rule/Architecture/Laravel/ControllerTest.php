<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

use function sprintf;

/**
 * Enforce Laravel controller placement, base class and repository isolation.
 *
 * Classes named `*Controller` must live under `<root>\Http\Controllers`, must extend
 * `Illuminate\Routing\Controller`, and may not depend on `<root>\Repositories\*` directly —
 * controllers go through a service layer instead.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ControllerTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class ControllerTest
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
                [self::selectByClassnameSuffix('Controller')],
                'Http\Controllers',
                'Controllers',
            );

            yield self::buildMustExtendRule(
                Selector::inNamespace($root . '\Http\Controllers'),
                'Illuminate\Routing\Controller',
                'Controllers must extend Illuminate\Routing\Controller.',
            );

            yield self::buildNamespaceIsolationRule(
                $root . '\Http\Controllers',
                $root . '\Repositories',
                sprintf('Controllers must not depend on %s\Repositories\* directly; use a service layer instead.', $root),
            );
        }
    }
}
