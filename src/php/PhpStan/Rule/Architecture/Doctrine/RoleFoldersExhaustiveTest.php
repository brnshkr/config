<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Doctrine;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Require every top-level folder directly under `<root>` to match a known Doctrine role.
 *
 * Catches stray top-level folders (typos, ad-hoc additions, orphaned legacy directories)
 * before they accumulate. Default whitelist covers the canonical Doctrine roles
 * (`Entity`, `Repository`); override `$allowedFolders` to extend.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(RoleFoldersExhaustiveTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class RoleFoldersExhaustiveTest
{
    use ArchitectureRuleTrait;

    public const array DEFAULT_ALLOWED_FOLDERS = [
        'Entity',
        'Repository',
    ];

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root Root application namespace
     * @param non-empty-list<non-empty-string> $allowedFolders Whitelisted top-level folder names
     */
    public function __construct(
        private string $root = Architecture::DEFAULT_ROOT,
        private array $allowedFolders = self::DEFAULT_ALLOWED_FOLDERS,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildRoleFoldersExhaustiveRule($this->root, $this->allowedFolders, 'Doctrine');
    }
}
