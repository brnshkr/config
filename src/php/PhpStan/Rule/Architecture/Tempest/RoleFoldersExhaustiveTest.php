<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Tempest;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Require every top-level folder directly under `<root>` to match a known Tempest role.
 *
 * Default whitelist covers the canonical Tempest folders (Console, Http, Model, Database).
 * Override `$allowedFolders` to extend.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(RoleFoldersExhaustiveTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class RoleFoldersExhaustiveTest
{
    use ArchitectureRuleTrait;

    public const array DEFAULT_ALLOWED_FOLDERS = [
        'Console',
        'Http',
        'Model',
        'Database',
    ];

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-list<non-empty-string> $roots root namespaces, one per module
     * @param non-empty-list<non-empty-string> $allowedFolders whitelisted top-level folder names
     */
    public function __construct(
        private array $roots,
        private array $allowedFolders = self::DEFAULT_ALLOWED_FOLDERS,
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
            yield self::buildRoleFoldersExhaustiveRule($root, $this->allowedFolders, 'Tempest');
        }
    }
}
