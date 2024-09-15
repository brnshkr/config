<?php

declare(strict_types=1);

namespace Brnshkr\Config;

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
    private function __construct() {}

    /**
     * @param 'error'|'info' $type
     *
     * @throws RuntimeException
     */
    public static function log(string $type, string $message): void
    {
        $message = sprintf(
            "%s[%s]\e[39m %s",
            $type === 'error' ? "\e[31m" : "\e[32m",
            ComposerJson::forThisLibrary()->getPackageFullName(),
            $message . PHP_EOL,
        );

        if ($type === 'error') {
            fwrite(STDERR, $message);
        } else {
            fwrite(STDOUT, $message);
        }
    }
}
