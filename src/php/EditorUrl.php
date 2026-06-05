<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use function array_any;
use function array_merge;
use function explode;
use function getcwd;
use function is_executable;
use function is_string;

use const PATH_SEPARATOR;

/**
 * Editor-jump URL template builder for static-analysis tools.
 *
 * Tools like PHPStan and Rector emit per-error links so the user can jump to the reported
 * location in their IDE. The shape of those links depends on the editor (`vscode://...`,
 * `phpstorm://...`) and on the tool's own placeholder syntax (PHPStan uses `%%relFile%%`,
 * Rector uses `%relFile%`). EditorUrl produces the right template for each (tool, editor) pair,
 * auto-detecting the editor from environment variables and `PATH` when none is given.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/EditorUrl.md
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class EditorUrl
{
    public const string EDITOR_VSCODE   = 'vscode';
    public const string EDITOR_PHPSTORM = 'phpstorm';

    private const string PLACEHOLDER_CWD        = '{cwd}';
    private const string PLACEHOLDER_FILE       = '{file}';
    private const string PLACEHOLDER_LINE       = '{line}';
    private const string PLACEHOLDER_WSL_DISTRO = '{wslDistro}';

    private const array EDITORS = [
        self::EDITOR_VSCODE => [
            'command'  => 'code',
            'template' => [
                'default' => 'vscode://file/' . self::PLACEHOLDER_CWD . '/' . self::PLACEHOLDER_FILE . ':' . self::PLACEHOLDER_LINE,
                'wsl'     => 'vscode://vscode-remote/wsl+' . self::PLACEHOLDER_WSL_DISTRO . self::PLACEHOLDER_CWD . '/' . self::PLACEHOLDER_FILE . ':' . self::PLACEHOLDER_LINE,
            ],
        ],
        self::EDITOR_PHPSTORM => [
            'command'  => ['pstorm', 'phpstorm'],
            'template' => 'phpstorm://open?file=' . self::PLACEHOLDER_CWD . '/' . self::PLACEHOLDER_FILE . '&line=' . self::PLACEHOLDER_LINE,
        ],
    ];

    private function __construct() {}

    /**
     * Build the editor-jump URL template that PHPStan substitutes per error.
     *
     * The returned template carries PHPStan's own `%%relFile%%` / `%%line%%` placeholders. The
     * working-directory prefix is either the caller-supplied path or, when none is given,
     * PHPStan's own `%currentWorkingDirectory%` substitution.
     *
     * @example
     * ```php
     * use Brnshkr\Config\EditorUrl;
     *
     * // Auto-detect the editor from the environment.
     * $editorUrl = EditorUrl::forPhpStan();
     *
     * // Pin the editor (and optionally the project root) when auto-detection picks the wrong one.
     * $editorUrl = EditorUrl::forPhpStan(EditorUrl::EDITOR_PHPSTORM, __DIR__);
     * ```
     *
     * @template TEditor of ?self::EDITOR_*
     *
     * @param TEditor $editor target editor identifier; auto-detected from the environment when null
     * @param ?string $currentWorkingDirectory absolute path prefixed to the file segment of the generated URL; PHPStan's own `%currentWorkingDirectory%` substitution is used when null
     *
     * @return (TEditor is null ? ?string : string) URL template ready to be passed as PHPStan's `editorUrl` parameter; `null` when no editor is configured or detected
     */
    public static function forPhpStan(?string $editor = null, ?string $currentWorkingDirectory = null): ?string
    {
        return self::build($editor, [
            'cwd'  => $currentWorkingDirectory ?? '%currentWorkingDirectory%',
            'file' => '%%relFile%%',
            'line' => '%%line%%',
        ]);
    }

    /**
     * Build the editor-jump URL template that Rector substitutes per error.
     *
     * The returned template carries Rector's `%relFile%` / `%line%` placeholders. The
     * working-directory prefix is resolved at build time — either from the caller-supplied
     * path or, when none is given, from `getcwd()` (falling back to `.` if even that fails).
     *
     * @example
     * ```php
     * use Brnshkr\Config\EditorUrl;
     *
     * // Auto-detect the editor and use the current working directory.
     * $editorUrl = EditorUrl::forRector();
     *
     * // Pin the editor (and optionally the project root) when auto-detection picks the wrong one.
     * $editorUrl = EditorUrl::forRector(EditorUrl::EDITOR_VSCODE, __DIR__);
     * ```
     *
     * @template TEditor of ?self::EDITOR_*
     *
     * @param TEditor $editor target editor identifier; auto-detected from the environment when null
     * @param ?string $currentWorkingDirectory absolute path prefixed to the file segment of the generated URL; `getcwd()` is used when null, falling back to `.` when `getcwd()` itself fails
     *
     * @return (TEditor is null ? ?string : string) URL template ready to be passed as Rector's `editorUrl` setting; `null` when no editor is configured or detected
     */
    public static function forRector(?string $editor = null, ?string $currentWorkingDirectory = null): ?string
    {
        return self::build($editor, [
            'cwd'  => ($currentWorkingDirectory ?? getcwd()) ?: '.',
            'file' => '%relFile%',
            'line' => '%line%',
        ]);
    }

    /**
     * @template TEditor of ?self::EDITOR_*
     *
     * @param TEditor $editor
     * @param array{
     *     cwd: string,
     *     file: string,
     *     line: string,
     * } $placeholders
     *
     * @return (TEditor is null ? ?string : string)
     */
    private static function build(?string $editor, array $placeholders): ?string
    {
        $environment = array_merge($_SERVER, $_ENV);
        $editor ??= self::getEditor($environment);

        if ($editor === null) {
            return null;
        }

        $config    = self::EDITORS[$editor];
        $wslDistro = self::getWslDistroName($environment);

        if (is_string($config['template'])) {
            $template = $config['template'];
        } else {
            $template = $wslDistro === null
                ? $config['template']['default']
                : $config['template']['wsl'];
        }

        $url = Str::replace($template, self::PLACEHOLDER_CWD, $placeholders['cwd']);
        $url = Str::replace($url, self::PLACEHOLDER_FILE, $placeholders['file']);
        $url = Str::replace($url, self::PLACEHOLDER_LINE, $placeholders['line']);

        return $wslDistro === null
            ? $url
            : Str::replace($url, self::PLACEHOLDER_WSL_DISTRO, $wslDistro);
    }

    /**
     * @param array<array-key, mixed> $environment
     *
     * @return ?self::EDITOR_*
     */
    private static function getEditor(array $environment): ?string
    {
        foreach ($environment as $key => $value) {
            $key = (string) $key;

            $editor = match (true) {
                Str::doesStartWith($key, 'VSCODE_')                             => self::EDITOR_VSCODE,
                Str::doesStartWith($key, 'PHPSTORM')                            => self::EDITOR_PHPSTORM,
                $key === 'TERMINAL_EMULATOR' && $value === 'JetBrains-JediTerm' => self::EDITOR_PHPSTORM,
                default                                                         => null,
            };

            if ($editor !== null) {
                return $editor;
            }
        }

        $paths = explode(PATH_SEPARATOR, is_string($environment['PATH'] ?? null) ? $environment['PATH'] : '');

        foreach (self::EDITORS as $candidate => $config) {
            if (self::isCommandAvailable($config['command'], $paths)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<array-key, mixed> $environment
     */
    private static function getWslDistroName(array $environment): ?string
    {
        return (isset($environment['WSL_DISTRO_NAME']) && is_string($environment['WSL_DISTRO_NAME']))
            ? $environment['WSL_DISTRO_NAME']
            : null;
    }

    /**
     * @param non-empty-string|non-empty-list<non-empty-string> $command
     * @param list<string> $paths
     */
    private static function isCommandAvailable(string|array $command, array $paths): bool
    {
        $commands = is_string($command) ? [$command] : $command;

        return array_any(
            $commands,
            static fn (string $command): bool => array_any(
                $paths,
                static fn (string $path): bool => is_executable(Str::trim($path, '/', 'end') . '/' . $command),
            ),
        );
    }
}
