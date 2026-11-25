<?php

declare(strict_types=1);

namespace Brnshkr\Config;

/**
 * @internal
 */
final readonly class ProjectDirectory
{
    public const string DEFAULT_CONFIG_DIRECTORY = 'conf';

    private const string DEFAULT_CACHE_DIRECTORY = '.cache';

    private const string DEFAULT_LOCAL_DIRECTORY = '.local';

    private function __construct() {}

    public static function getConfig(): string
    {
        return self::fromEnvironment('BRNSHKR_CONFIG_DIR', self::DEFAULT_CONFIG_DIRECTORY);
    }

    public static function getCache(): string
    {
        return self::fromEnvironment('BRNSHKR_CACHE_DIR', self::DEFAULT_CACHE_DIRECTORY);
    }

    public static function getLocal(): string
    {
        return self::fromEnvironment('BRNSHKR_LOCAL_DIR', self::DEFAULT_LOCAL_DIRECTORY);
    }

    private static function fromEnvironment(string $variableName, string $defaultDirectory): string
    {
        $configuredDirectory = Str::trimPrefix(Str::fromEnvironment($variableName), './');

        return Str::isEmpty($configuredDirectory) ? $defaultDirectory : $configuredDirectory;
    }
}
