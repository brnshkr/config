<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Trait;

use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\After;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

use function bin2hex;
use function random_bytes;
use function sprintf;

/**
 * @internal
 */
trait TemporaryRootTrait
{
    private static bool $hasRemovedStaleTemporaryRoots = false;

    /**
     * @var list<non-empty-string>
     */
    private array $temporaryRoots = [];

    #[After]
    public function removeTheTemporaryRoots(): void
    {
        $temporaryDirectory = self::getTemporaryDirectory() . '/';

        foreach ($this->temporaryRoots as $temporaryRoot) {
            if (!Str::startsWith($temporaryRoot, $temporaryDirectory)) {
                throw new RuntimeException(sprintf(
                    'The path "%s" is not within "%s".',
                    $temporaryRoot,
                    $temporaryDirectory,
                ));
            }
        }

        new Filesystem()->remove($this->temporaryRoots);
    }

    /**
     * @param non-empty-string $prefix
     *
     * @return non-empty-string
     */
    private function createTemporaryRoot(string $prefix): string
    {
        self::removeStaleTemporaryRoots();

        $temporaryRoot = self::getTemporaryDirectory() . '/' . $prefix . bin2hex(random_bytes(6));

        $this->temporaryRoots[] = $temporaryRoot;

        return $temporaryRoot;
    }

    /**
     * @param non-empty-string $prefix
     *
     * @return non-empty-string
     */
    private function createMarkerPath(string $prefix): string
    {
        $temporaryRoot = $this->createTemporaryRoot($prefix);

        new Filesystem()->mkdir($temporaryRoot);

        return $temporaryRoot . '/ran';
    }

    private static function getTemporaryDirectory(): string
    {
        $homeDirectory = Str::fromEnvironment('HOME') ?: throw new RuntimeException('Environment variable not found: "HOME".');

        return Path::canonicalize($homeDirectory . '/.cache/brnshkr-tests');
    }

    private static function removeStaleTemporaryRoots(): void
    {
        if (self::$hasRemovedStaleTemporaryRoots) {
            return;
        }

        self::$hasRemovedStaleTemporaryRoots = true;

        try {
            $staleRoots = new Finder()
                ->directories()
                ->in(self::getTemporaryDirectory())
                ->depth(0)
                ->date('< 1 day ago')
            ;
        } catch (DirectoryNotFoundException) {
            return;
        }

        new Filesystem()->remove($staleRoots);
    }
}
