<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer\Command;

use BadMethodCallException;
use InvalidArgumentException;
use LogicException;
use Override;
use Phar;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function array_filter;
use function count;
use function explode;
use function getcwd;
use function glob;
use function is_dir;
use function is_file;
use function is_string;
use function mkdir;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal Brnshkr\Config\Composer
 */
final class ExtractPharCommand extends AbstractCommand
{
    private Filesystem $filesystem;

    #[Override]
    protected function getDescriptionTemplate(): string
    {
        return 'Extracts a .phar file from a given vendor package';
    }

    #[Override]
    protected function wrappedInitialize(): void
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Override]
    protected function wrappedConfigure(): void
    {
        $this
            ->addArgument('package', InputArgument::REQUIRED, 'The vendor/package name to extract the .phar from')
            ->addOption('target-directory', 't', InputArgument::OPTIONAL, 'The directory to extract the .phar to')
        ;
    }

    /**
     * @phpstan-return self::SUCCESS
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws RuntimeException
     */
    #[Override]
    protected function wrappedExecute(): int
    {
        $package         = $this->getPackage();
        $cwd             = getcwd() ?: '.';
        $vendorDirectory = $cwd . '/vendor/' . $package;
        $targetDirectory = $this->getTargetDirectory() ?? ($vendorDirectory . '/.extracted-phar');
        $targetDirectory = Path::makeAbsolute($targetDirectory, $cwd);
        $pharPath        = self::getPharPath($vendorDirectory);

        $this->console->writeNotice(sprintf(
            'Extracting ./%s to ./%s',
            s($this->filesystem->makePathRelative($pharPath, $cwd))->trimEnd('/'),
            s($this->filesystem->makePathRelative($targetDirectory, $cwd))->trimEnd('/'),
        ));

        self::extractPhar($pharPath, $targetDirectory);

        return self::SUCCESS;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getPackage(): string
    {
        $package = $this->input->getArgument('package');

        if (!is_string($package)) {
            throw new InvalidArgumentException('Argument "package" must be a string.');
        }

        $packageParts = array_filter(explode('/', $package), boolval(...));

        if (count($packageParts) !== 2) {
            throw new InvalidArgumentException('Argument "package" must be in the format "vendor/package".');
        }

        return $package;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getTargetDirectory(): ?string
    {
        $targetDirectory = $this->input->getOption('target-directory');

        if ($targetDirectory !== null) {
            if (!is_string($targetDirectory)) {
                throw new InvalidArgumentException('Option "target-directory" must be a string.');
            }

            if ($targetDirectory === '') {
                throw new InvalidArgumentException('Option "target-directory" must not be empty.');
            }
        }

        return $targetDirectory;
    }

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private static function getPharPath(string $vendorDirectory): string
    {
        if (!is_dir($vendorDirectory)) {
            throw new InvalidArgumentException(sprintf('Package directory "%s" does not exist.', $vendorDirectory));
        }

        $phars = glob($vendorDirectory . '/*.phar');

        if ($phars === false || $phars === [] || !is_file($phars[0])) {
            throw new RuntimeException(sprintf('No .phar binary found in "%s".', $vendorDirectory));
        }

        return $phars[0];
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    private static function extractPhar(string $pharPath, string $targetDirectory): void
    {
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, recursive: true) && !is_dir($targetDirectory)) {
            throw new RuntimeException(sprintf('Failed to create directory "%s".', $targetDirectory));
        }

        try {
            new Phar($pharPath)->extractTo($targetDirectory, overwrite: true);
        } catch (BadMethodCallException) {
            throw new LogicException('Unreachable.');
        }
    }
}
