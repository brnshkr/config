<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Tool;

use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Str;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;

use function array_keys;
use function sprintf;

/**
 * Runs the quality tools of this repository.
 *
 * @internal
 */
final class QualityTool
{
    private const array COMMAND_MAP = [
        'phpstan' => [
            'check' => ['make', 'NO_ANSI=1', 'phpstan'],
            'fix'   => ['make', 'NO_ANSI=1', 'phpstan'],
        ],
        'php-cs-fixer' => [
            'check' => ['make', 'NO_ANSI=1', 'php-cs-fixer-dry-run'],
            'fix'   => ['make', 'NO_ANSI=1', 'php-cs-fixer'],
        ],
        'rector' => [
            'check' => ['make', 'NO_ANSI=1', 'rector-dry-run'],
            'fix'   => ['make', 'NO_ANSI=1', 'rector'],
        ],
        'twig-cs-fixer' => [
            'check' => ['make', 'NO_ANSI=1', 'twig-cs-fixer-dry-run'],
            'fix'   => ['make', 'NO_ANSI=1', 'twig-cs-fixer'],
        ],
        'eslint' => [
            'check' => ['make', 'NO_ANSI=1', 'eslint-dry-run'],
            'fix'   => ['make', 'NO_ANSI=1', 'eslint'],
        ],
        'markdownlint' => [
            'check' => ['make', 'NO_ANSI=1', 'markdownlint-dry-run'],
            'fix'   => ['make', 'NO_ANSI=1', 'markdownlint'],
        ],
        'stylelint' => [
            'check' => ['make', 'NO_ANSI=1', 'stylelint-dry-run'],
            'fix'   => ['make', 'NO_ANSI=1', 'stylelint'],
        ],
        'typescript' => [
            'check' => ['make', 'NO_ANSI=1', 'typescript'],
            'fix'   => ['make', 'NO_ANSI=1', 'typescript'],
        ],
    ];

    /**
     * @param string $tool the tool to run, one of the keys of {@see self::COMMAND_MAP}
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
        $commands = self::COMMAND_MAP[$tool] ?? null;

        if ($commands === null) {
            return Project::encode([
                'exitCode' => 1,
                'output'   => sprintf(
                    'Unknown tool "%s". Valid tools: %s.',
                    $tool,
                    Str::joinAsQuotedList(array_keys(self::COMMAND_MAP)),
                ),
            ]);
        }

        return Project::encode(Project::run($isDryRun ? $commands['check'] : $commands['fix']));
    }
}
