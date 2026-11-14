<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use Brnshkr\Config\Tests\ProperNamesTest;

use function array_map;
use function usort;

/**
 * @internal
 *
 * @see https://github.com/brnshkr/config/blob/master/src/js/shared/utils/terms.ts
 * @see ProperNamesTest
 */
final readonly class ProperNames
{
    private const array TERMS = [
        'actionlint',
        'Bun',
        'Caddy',
        'commitlint',
        'CommonMark',
        'Dependabot',
        'Doctrine',
        'ESLint',
        'FrankenPHP',
        'GitHub',
        'GitHub Actions',
        'GitLab',
        'Gitleaks',
        'hadolint',
        'ICU',
        'JavaScript',
        'JSDoc',
        'JSONC',
        'Laravel',
        'Markdown',
        'markdownlint',
        'Node.js',
        'OCI',
        'Packagist',
        'PCRE',
        'PHP',
        'PHP-CS-Fixer',
        'PHPat',
        'PHPStan',
        'PHPUnit',
        'pnpm',
        'PostCSS',
        'PSR',
        'Rector',
        'SARIF',
        'Sass',
        'SCSS',
        'Semgrep',
        'SemVer',
        'ShellCheck',
        'Stylelint',
        'SvelteKit',
        'Symfony',
        'Tailwind CSS',
        'TOML',
        'Trivy',
        'TSDoc',
        'tsdown',
        'Twig',
        'TypeScript',
        'typescript-eslint',
        'Valkey',
        'Vitest',
        'VS Code',
        'WCAG',
        'WSL',
        'Xdebug',
        'Yarn',
    ];

    private const string PROTECTED_PATTERN = '```[\s\S]*?```|`[^`\n]*`|https?:\/\/\S+|\{@[^}\n]*\}'
        . '|(?m:^[^\S\n]*(?:\/\*\*?|\*|\/\/|#)?[^\S\n]*@[\w-]+(?:[^\n]*? - |(?<=@return|@returns|@throws)[^\S\n]+\S+|[^\n]*))';

    private const array SEPARATOR_PATTERNS = [
        '-' => '-?',
        '.' => '\.?',
        ' ' => ' ?',
    ];

    public static function correct(string $comment): string
    {
        return Str::replaceMatches(
            $comment,
            '/(?<protected>' . self::PROTECTED_PATTERN . ')|(?:(?!' . self::PROTECTED_PATTERN . ')[\s\S])+/',
            static fn (array $matches): string => ($matches['protected'] ?? '') === ''
                ? self::correctProse($matches[0] ?? '')
                : $matches[0] ?? '',
        );
    }

    private static function correctProse(string $prose): string
    {
        $terms = self::TERMS;

        usort($terms, static fn (string $first, string $second): int => Str::length($second) <=> Str::length($first));

        foreach ($terms as $term) {
            $prose = Str::replaceMatches($prose, self::buildPattern($term), static fn (): string => $term);
        }

        return $prose;
    }

    private static function buildPattern(string $term): string
    {
        $exactSpellings = Str::join(array_map(
            static fn (string $spelling): string => Str::quoteRegex($spelling),
            [$term, Str::toLowerCase($term)],
        ), '|');

        $anyCasing = Str::replaceMatches(
            $term,
            '/./',
            static fn (array $matches): string => self::SEPARATOR_PATTERNS[$matches[0] ?? '']
                ?? '[' . Str::quoteRegex(Str::toUpperCase($matches[0] ?? '')) . Str::quoteRegex(Str::toLowerCase($matches[0] ?? '')) . ']',
        );

        return '/(?<![\w.\/@\\\$-])(?<!::)(?!(?:' . $exactSpellings . ')(?![\w\\\-]|::))' . $anyCasing . '(?![\w\\\-]|::)/';
    }
}
