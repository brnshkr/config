<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Tempest;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
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
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ConsoleNoHttpTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class ConsoleNoHttpTest
{
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
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::inNamespace($this->root),
                Selector::appliesAttribute('Tempest\Console\ConsoleCommand'),
            ))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::inNamespace('Tempest\Http'))
            ->because('Console commands must not depend on Tempest\Http.')
        ;
    }
}
