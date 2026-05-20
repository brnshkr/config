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
 * @internal Brnshkr\Config
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
     * @template TEditor of ?self::EDITOR_*
     *
     * @param TEditor $editor
     *
     * @return (TEditor is null ? ?string : string)
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
     * @template TEditor of ?self::EDITOR_*
     *
     * @param TEditor $editor
     *
     * @return (TEditor is null ? ?string : string)
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
