<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Tempest;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

/**
 * Forbid Tempest console commands from depending on `Tempest\Http`.
 *
 * Classes under `<root>` annotated with `#[Tempest\Console\ConsoleCommand]` run outside the
 * HTTP request lifecycle; touching HTTP types couples console workflows to a transport they
 * never use.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ConsoleNoHttpTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class ConsoleNoHttpTest
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
                    Selector::appliesAttribute('Tempest\Console\ConsoleCommand'),
                ))
                ->shouldNot()
                ->dependOn()
                ->classes(Selector::inNamespace('Tempest\Http'))
                ->because('Console commands must not depend on Tempest\Http.')
            ;
        }
    }
}
