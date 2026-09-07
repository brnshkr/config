<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Laravel service provider placement and base class.
 *
 * Classes named `*ServiceProvider` must live under `<root>\Providers`, and every
 * `*ServiceProvider` in that folder must extend `Illuminate\Support\ServiceProvider`.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ServiceProviderTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class ServiceProviderTest
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
                [self::selectByClassnameSuffix('ServiceProvider')],
                'Providers',
                'Service providers',
            );

            yield self::buildMustExtendRule(
                Selector::AllOf(
                    Selector::inNamespace($root . '\Providers'),
                    self::selectByClassnameSuffix('ServiceProvider'),
                ),
                'Illuminate\Support\ServiceProvider',
                'Service providers must extend Illuminate\Support\ServiceProvider.',
            );
        }
    }
}
