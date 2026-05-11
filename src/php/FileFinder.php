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
 * @api
 *
 * @no-named-arguments
 */
final readonly class FileFinder
{
    public const string EXTENSION_PHP  = 'php';
    public const string EXTENSION_TWIG = 'twig';

    /**
     *  @phpstan-var list<self::EXTENSION_*>
     */
    public const array EXTENSIONS = [
        self::EXTENSION_PHP,
        self::EXTENSION_TWIG,
    ];

    private function __construct() {}

    /**
     * @param self::EXTENSION_*|list<self::EXTENSION_*> $extensions
     *
     * @throws DirectoryNotFoundException
     * @throws InvalidArgumentException
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
            $finder = (new Finder())->in('.');
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
                $finder->append((new Finder())->files()->in('bin')->name('console'));
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
                'tests/coverage',
                '/^tests(?:\/.+)?\/Fixtures/',
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
     * @param self::EXTENSION_*|list<self::EXTENSION_*> $extensions
     *
     * @return list<string>
     *
     * @throws DirectoryNotFoundException
     * @throws InvalidArgumentException
     */
    public static function getFilePaths(?Finder $finder = null, string|array $extensions = self::EXTENSION_PHP): array
    {
        return array_keys(iterator_to_array(self::get($finder, $extensions)));
    }
}
