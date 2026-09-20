<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Invariant;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

/**
 * Forbid production code from depending on a namespace that only development autoloads.
 *
 * A package export-ignores its tests, so a production class naming one resolves locally and fails
 * for every consumer. The namespaces are the project's own `autoload-dev` entries, which is the
 * test suite plus anything else it loads beside it. Part of every preset.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(NoTestDependencyTest::class, [
 *     'root'                  => 'Acme',
 *     'developmentNamespaces' => ['Acme\Tests'],
 * ]);
 * ```
 */
final readonly class NoTestDependencyTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root production namespace
     * @param non-empty-list<non-empty-string> $developmentNamespaces namespaces autoloaded for development only
     */
    public function __construct(
        private string $root,
        private array $developmentNamespaces,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return PHPat::rule()
            ->classes(self::selectProductionClassesIn($this->root, $this->developmentNamespaces))
            ->shouldNot()
            ->dependOn()
            ->classes(...self::buildNamespaceSelectors($this->developmentNamespaces))
            ->because('Production code must not depend on a namespace autoloaded for development only; it is not shipped.')
        ;
    }
}
