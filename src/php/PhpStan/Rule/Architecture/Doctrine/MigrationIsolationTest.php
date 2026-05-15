<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Doctrine;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

use function sprintf;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class MigrationIsolationTest
{
    use ArchitectureRuleTrait;

    /**
     * @param non-empty-string $root
     * @param non-empty-string $migrationsNamespace
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
