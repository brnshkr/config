<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use Brnshkr\Config\Exception\UnreachableException;
use RuntimeException;

use function fwrite;
use function sprintf;

use const PHP_EOL;
use const STDERR;
use const STDOUT;

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

        if ($level === 'error' || $level === 'warn') {
            fwrite(STDERR, $message);
        } else {
            fwrite(STDOUT, $message);
        }
    }
}
