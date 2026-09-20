<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Library;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Require every exception a package throws to implement that package's own exception interface.
 *
 * It is what lets a consumer catch everything one package can throw with a single `catch`, so an
 * exception missing it is unreachable that way and nothing else says so.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ExceptionInterfaceTest::class, [
 *     'root'      => 'Acme',
 *     'interface' => 'Acme\Exception\ExceptionInterface',
 * ]);
 * ```
 */
final readonly class ExceptionInterfaceTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root production namespace
     * @param non-empty-string $interface the package's own exception interface
     */
    public function __construct(
        private string $root,
        private string $interface,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildMustImplementRule(
            Selector::AllOf(Selector::inNamespace($this->root), Selector::isException()),
            $this->interface,
            'Exceptions must implement the package exception interface, so one catch covers the package.',
        );
    }
}
