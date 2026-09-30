<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use Brnshkr\Config\Exception\UnreachableException;
use RuntimeException;

use function fwrite;
use function in_array;
use function is_resource;
use function sprintf;
use function stream_isatty;

use const PHP_EOL;
use const STDERR;

/**
 * @internal
 */
final readonly class Logger
{
    public const string ANSI_RED              = "\033[31m";
    public const string ANSI_GREEN            = "\033[32m";
    public const string ANSI_YELLOW           = "\033[33m";
    public const string ANSI_MAGENTA          = "\033[35m";
    public const string ANSI_CYAN             = "\033[36m";
    public const string ANSI_WHITE_UNDERLINED = "\033[4;37m";
    public const string ANSI_RESET            = "\033[0m";

    private const array ANSI_CODES = [
        self::ANSI_RED,
        self::ANSI_GREEN,
        self::ANSI_YELLOW,
        self::ANSI_MAGENTA,
        self::ANSI_CYAN,
        self::ANSI_WHITE_UNDERLINED,
        self::ANSI_RESET,
    ];

    private const array FALSE_VALUES = [
        '0',
        'false',
        'off',
        'no',
    ];

    private function __construct() {}

    /**
     * @param 'debug'|'error'|'info'|'notice'|'warn' $level
     */
    public static function log(string $level, string $message): void
    {
        $ansiColorCode = match ($level) {
            'debug'  => self::ANSI_CYAN,
            'error'  => self::ANSI_RED,
            'info'   => self::ANSI_GREEN,
            'notice' => self::ANSI_MAGENTA,
            'warn'   => self::ANSI_YELLOW,
        };

        try {
            $packageName = ComposerJson::forThisLibrary()->getPackageFullName();
        } catch (RuntimeException $runtimeException) {
            throw UnreachableException::wrap($runtimeException);
        }

        $message = sprintf(
            '%s[%s]%s %s',
            $ansiColorCode,
            $packageName,
            self::ANSI_RESET,
            $message . PHP_EOL,
        );

        if (!self::isColored()) {
            foreach (self::ANSI_CODES as $ansiCode) {
                $message = Str::replace($message, $ansiCode, '');
            }
        }

        fwrite(STDERR, $message);
    }

    private static function isColored(): bool
    {
        if (Str::fromEnvironment('NO_COLOR') !== '') {
            return false;
        }

        if (self::isEnvironmentFlagOn('FORCE_COLOR')
            || self::isEnvironmentFlagOn('CLICOLOR_FORCE')) {
            return true;
        }

        if (in_array(Str::fromEnvironment('CLICOLOR'), self::FALSE_VALUES, true)
            || Str::fromEnvironment('TERM') === 'dumb') {
            return false;
        }

        return self::isEnvironmentFlagOn('CI')
            || (is_resource(STDERR) && stream_isatty(STDERR));
    }

    private static function isEnvironmentFlagOn(string $name): bool
    {
        $value = Str::fromEnvironment($name);

        return $value !== ''
            && !in_array($value, self::FALSE_VALUES, true);
    }
}
