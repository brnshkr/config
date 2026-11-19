<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan;

use App\Kernel;
use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\ProjectDirectory;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\PhpStan\ProjectKernelTest;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

use function array_keys;
use function getcwd;
use function is_file;
use function is_readable;
use function iterator_to_array;
use function realpath;
use function sprintf;
use function uksort;

/**
 * @internal Brnshkr\Config
 *
 * @no-named-arguments
 *
 * @see ProjectKernelTest
 */
final readonly class ProjectKernel
{
    public const string CLASS_ENVIRONMENT_VARIABLE = 'PHPSTAN_KERNEL_CLASS';

    private const string DEFAULT_CLASS = Kernel::class;

    private const string DEFAULT_ENVIRONMENT = 'dev';

    private const string CACHE_DIRECTORY = 'var/cache';

    private const string CONTAINER_FILE_PATTERN = '*Container.xml';

    private const string LOADER_DIRECTORY = 'phpstan';

    private function __construct() {}

    /**
     * @return non-empty-string
     */
    public static function getClassName(): string
    {
        return self::getConfiguredClassName() ?? self::DEFAULT_CLASS;
    }

    /**
     * @return non-empty-string
     */
    public static function getEnvironment(): string
    {
        $environment = Str::trim(Str::fromEnvironment('APP_ENV'));

        return Str::isEmpty($environment) ? self::DEFAULT_ENVIRONMENT : $environment;
    }

    /**
     * @return non-empty-string
     */
    public static function getRootDirectory(): string
    {
        try {
            $projectPath = ComposerJson::forProjectUsingThisLibrary()->getDirectory();
        } catch (RuntimeException) {
            return getcwd() ?: '.';
        }

        return realpath($projectPath) ?: (getcwd() ?: '.');
    }

    /**
     * @return ?non-empty-string
     *
     * @throws RuntimeException
     */
    public static function locate(): ?string
    {
        $className    = self::getClassName();
        $filesystem   = new Filesystem();
        $namespaceMap = ComposerJson::forProjectUsingThisLibrary()->getNamespaceMap();

        uksort($namespaceMap, static fn (string $left, string $right): int => Str::length($right) <=> Str::length($left));

        foreach ($namespaceMap as $namespacePrefix => $directory) {
            if (!Str::startsWith($className, $namespacePrefix)) {
                continue;
            }

            $path = sprintf(
                '%s/%s/%s.php',
                self::getRootDirectory(),
                Str::trimSuffix($directory, '/'),
                Str::replace(Str::slice($className, Str::length($namespacePrefix)), '\\', '/'),
            );

            if ($filesystem->exists($path)) {
                return realpath($path) ?: $path;
            }
        }

        if (self::getConfiguredClassName() !== null) {
            throw new RuntimeException(sprintf(
                'The kernel class "%s" named by %s declares no file under the project\'s PSR-4 map. Correct the class name or unset the variable.',
                $className,
                self::CLASS_ENVIRONMENT_VARIABLE,
            ));
        }

        return null;
    }

    /**
     * @return ?non-empty-string
     *
     * @throws DirectoryNotFoundException
     */
    public static function locateContainerXml(): ?string
    {
        $directory = sprintf('%s/%s/%s', self::getRootDirectory(), self::CACHE_DIRECTORY, self::getEnvironment());

        if (!new Filesystem()->exists($directory)) {
            return null;
        }

        $containerPaths = array_keys(iterator_to_array(new Finder()
            ->files()
            ->in($directory)
            ->depth(0)
            ->name(self::CONTAINER_FILE_PATTERN)
            ->sortByName()));

        return $containerPaths[0] ?? null;
    }

    /**
     * @param non-empty-string $name
     *
     * @return non-empty-string
     *
     * @throws RuntimeException when the project's composer.json cannot be read
     */
    public static function getLoaderPath(string $name): string
    {
        $projectPath = sprintf(
            '%s/%s/%s/%s.php',
            ComposerJson::forProjectUsingThisLibrary()->getDirectory(),
            ProjectDirectory::getConfig(),
            self::LOADER_DIRECTORY,
            $name,
        );

        if (is_file($projectPath) && is_readable($projectPath)) {
            return $projectPath;
        }

        $path = sprintf('%s/../../../%s/%s/%s.php', __DIR__, ProjectDirectory::DEFAULT_CONFIG_DIRECTORY, self::LOADER_DIRECTORY, $name);

        return realpath($path) ?: $path;
    }

    /**
     * @return ?non-empty-string
     */
    private static function getConfiguredClassName(): ?string
    {
        $configured = Str::trim(Str::fromEnvironment(self::CLASS_ENVIRONMENT_VARIABLE));

        return Str::isEmpty($configured) ? null : $configured;
    }
}
