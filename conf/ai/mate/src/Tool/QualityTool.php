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
            'check' => ['bun', '--bun', 'run', 'eslint'],
            'fix'   => ['bun', '--bun', 'run', 'eslint', '--fix'],
        ],
        'stylelint' => [
            'check' => ['bun', '--bun', 'run', 'stylelint'],
            'fix'   => ['bun', '--bun', 'run', 'stylelint', '--fix'],
        ],
        'typescript' => [
            'check' => ['bun', '--bun', 'run', 'typescript'],
            'fix'   => ['bun', '--bun', 'run', 'typescript'],
        ],
    ];

    /**
     * @param string $tool the tool to run (phpstan, php-cs-fixer, rector, twig-cs-fixer, eslint, stylelint or typescript)
     * @param bool $isDryRun when true (default), runs the non-mutating dry-run variant; phpstan and typescript are always non-mutating
     *
     * @return array{
     *     exitCode: int,
     *     output: string,
     * }
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    #[McpTool(
        name: 'project-quality-check',
        description: 'Runs one of the quality tools of this repository (PHP: phpstan, php-cs-fixer, rector, twig-cs-fixer; JS: eslint, stylelint, typescript) and returns its output. Defaults to dry-run; pass isDryRun=false to apply fixes (phpstan and typescript are check-only).',
    )]
    public function runQualityTool(string $tool, bool $isDryRun = true): array
    {
        $commands = self::COMMAND_MAP[$tool] ?? null;

        if ($commands === null) {
            return [
                'exitCode' => 1,
                'output'   => sprintf(
                    'Unknown tool "%s". Valid tools: %s.',
                    $tool,
                    Str::joinAsQuotedList(array_keys(self::COMMAND_MAP)),
                ),
            ];
        }

        return Project::run($isDryRun ? $commands['check'] : $commands['fix']);
    }
}
