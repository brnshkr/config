<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Invariant;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

/**
 * Forbid production code from depending on a package that only development installs.
 *
 * `composer install --no-dev` is what runs in production, so a production class naming one of
 * those fails there and nowhere else. A package the project both dev-requires and suggests is an
 * optional dependency rather than tooling, and is not covered here. Part of every preset.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(NoDevelopmentDependencyTest::class, [
 *     'root'                => 'Acme',
 *     'forbiddenNamespaces' => ['PHPUnit', 'Rector'],
 * ]);
 * ```
 */
final readonly class NoDevelopmentDependencyTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root production namespace
     * @param non-empty-list<non-empty-string> $forbiddenNamespaces namespaces of the development-only packages
     * @param list<non-empty-string> $excludedNamespaces namespaces autoloaded for development only, which the rule does not cover
     */
    public function __construct(
        private string $root,
        private array $forbiddenNamespaces,
        private array $excludedNamespaces = [],
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return PHPat::rule()
            ->classes(self::selectProductionClassesIn($this->root, $this->excludedNamespaces))
            ->shouldNot()
            ->dependOn()
            ->classes(...self::buildNamespaceSelectors($this->forbiddenNamespaces))
            ->because('Production code must not depend on a development-only package; production installs without it.')
        ;
    }
}
