<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use InvalidArgumentException;
use LogicException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

use function array_keys;
use function array_map;
use function implode;
use function in_array;
use function is_array;
use function iterator_to_array;
use function sprintf;

/**
 * Shared file-discovery helper that every tool config in this package delegates to.
 *
 * Excludes `vendor`, `node_modules`, `var`, `.cache`, `.local`, `config/reference.php`, and the
 * `tests/**\/Fixtures` / `tests/**\/coverage` folders; filters by PHP and/or Twig extensions; and
 * also picks up `bin/console` when PHP files are requested. Callers may pass a pre-configured
 * Symfony Finder to narrow the scope further, otherwise the current working directory is scanned.
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class FileFinder
{
    public const string EXTENSION_PHP  = 'php';
    public const string EXTENSION_TWIG = 'twig';

    /**
     * @phpstan-var non-empty-list<self::EXTENSION_*>
     */
    public const array EXTENSIONS = [
        self::EXTENSION_PHP,
        self::EXTENSION_TWIG,
    ];

    private function __construct() {}

    /**
     * Build a Finder filtered to the requested extensions with project-wide exclusions applied.
     *
     * Reuses the caller's Finder when provided (calling `in('.')` if no source directory is set);
     * otherwise constructs a new Finder rooted at the current working directory.
     *
     * @example
     * ```php
     * $phpFiles = FileFinder::get();
     * $bothExt  = FileFinder::get(null, [FileFinder::EXTENSION_PHP, FileFinder::EXTENSION_TWIG]);
     * $scoped   = FileFinder::get(new Finder()->in('src'), FileFinder::EXTENSION_PHP);
     * ```
     *
     * @param ?Finder $finder Pre-configured Finder to extend, or null to scan the working directory
     * @param self::EXTENSION_*|list<self::EXTENSION_*> $extensions File extensions to include
     *
     * @return Finder Configured Finder ready for iteration
     *
     * @throws DirectoryNotFoundException When the resolved source directory does not exist
     * @throws InvalidArgumentException When an extension outside {@see self::EXTENSIONS} is passed
     */
    public static function get(?Finder $finder = null, string|array $extensions = self::EXTENSION_PHP): Finder
    {
        if ($finder instanceof Finder) {
            try {
                $finder->getIterator();
            } catch (LogicException) {
                $finder->in('.');
            }
        } else {
            $finder = new Finder()->in('.');
        }

        $extensions = is_array($extensions) ? $extensions : [$extensions];

        $namePatterns = array_map(static function (string $extension): string {
            if (!in_array($extension, self::EXTENSIONS, true)) {
                throw new InvalidArgumentException(sprintf('Unsupported extension "%s". Supported extensions are: %s.', $extension, implode(', ', self::EXTENSIONS)));
            }

            return '/\.' . $extension . '$/';
        }, $extensions);

        if ($namePatterns !== []) {
            $finder->name($namePatterns);
        }

        if (in_array(self::EXTENSION_PHP, $extensions, true)) {
            try {
                $finder->append(new Finder()->files()->in('bin')->name('console'));
            } catch (DirectoryNotFoundException) {
                // Ignore
            }
        }

        return $finder
            ->files()
            ->ignoreDotFiles(false)
            ->sortByCaseInsensitiveName(true)
            ->notPath([
                'config/reference.php',
                '/^tests(?:\/.+)?\/Fixtures/',
                '/^tests(?:\/.+)?\/coverage/',
            ])
            ->exclude([
                '.cache',
                '.local',
                'node_modules',
                'var',
                'vendor',
            ])
        ;
    }

    /**
     * Return matched file paths as a flat list of strings.
     *
     * Convenience over {@see self::get()} for callers that only need the absolute paths,
     * not the underlying SplFileInfo objects.
     *
     * @example
     * ```php
     * $paths = FileFinder::getFilePaths(extensions: FileFinder::EXTENSION_PHP);
     * ```
     *
     * @param ?Finder $finder Pre-configured Finder to extend, or null to scan the working directory
     * @param self::EXTENSION_*|list<self::EXTENSION_*> $extensions File extensions to include
     *
     * @return list<non-empty-string> Absolute paths to matched files
     *
     * @throws DirectoryNotFoundException When the resolved source directory does not exist
     * @throws InvalidArgumentException When an extension outside {@see self::EXTENSIONS} is passed
     */
    public static function getFilePaths(?Finder $finder = null, string|array $extensions = self::EXTENSION_PHP): array
    {
        return self::get($finder, $extensions)
            |> iterator_to_array(...)
            |> array_keys(...);
    }
}
