<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Tool;

use Brnshkr\Config\Mate\Support\Project;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;

use function array_fill_keys;
use function in_array;

/**
 * Runs the quality tools of this repository.
 *
 * @internal
 */
final class QualityTool
{
    /**
     * @phpstan-var non-empty-list<non-empty-string>
     */
    private const array TOOLS = [
        'phpstan',
        'php-cs-fixer',
        'rector',
        'twig-cs-fixer',
        'eslint',
        'markdownlint',
        'stylelint',
        'typescript',
    ];

    /**
     * @phpstan-var non-empty-list<non-empty-string>
     */
    private const array CHECK_ONLY_TOOLS = [
        'phpstan',
        'typescript',
    ];

    /**
     * @phpstan-var non-empty-list<non-empty-string>
     */
    private const array EDITOR_DETECTION_VARIABLES = [
        'VSCODE_PID',
        'VSCODE_CWD',
        'JETBRAINS_IDE',
        'VIM',
        'NVIM',
    ];

    /**
     * @param string $tool the tool to run, one of {@see self::TOOLS}
     * @param bool $isDryRun when true (default), runs the non-mutating dry-run variant; phpstan and typescript are always non-mutating
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    #[McpTool(
        name: 'project-quality-check',
        description: 'Runs one of the quality tools of this repository (PHP: phpstan, php-cs-fixer, rector, twig-cs-fixer; JS: eslint, markdownlint, stylelint, typescript) and returns its output. Defaults to dry-run; pass isDryRun=false to apply fixes (phpstan and typescript are check-only).',
    )]
    public function runQualityTool(string $tool, bool $isDryRun = true): string
    {
        if (!in_array($tool, self::TOOLS, true)) {
            return Project::encodeUnknownValue('tool', $tool, self::TOOLS);
        }

        return Project::encode(Project::runTarget(
            $isDryRun && !in_array($tool, self::CHECK_ONLY_TOOLS, true) ? $tool . '-dry-run' : $tool,
            $tool === 'eslint' ? array_fill_keys(self::EDITOR_DETECTION_VARIABLES, '') : [],
        ));
    }
}
