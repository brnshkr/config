<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Doctrine;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

use function sprintf;

/**
 * Forbid Doctrine migrations from depending on application code.
 *
 * Migrations must be self-contained and replayable in isolation: referencing application
 * classes ties past migrations to current code shape, breaking schema rebuilds when
 * referenced classes are renamed or removed.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(MigrationIsolationTest::class, [
 *     'root'                => 'Acme',
 *     'migrationsNamespace' => 'DoctrineMigrations',
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class MigrationIsolationTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root root application namespace forbidden inside migrations
     * @param non-empty-string $migrationsNamespace namespace containing Doctrine migration classes
     */
    public function __construct(
        private string $root = 'App',
        private string $migrationsNamespace = 'DoctrineMigrations',
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->migrationsNamespace,
            $this->root,
            sprintf('Migrations must not depend on %s application code.', $this->root),
        );
    }
}
