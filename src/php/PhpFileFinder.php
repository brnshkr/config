<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

/**
 * @no-named-arguments
 */
final class PhpFileFinder extends Finder
{
    /**
     * @throws DirectoryNotFoundException
     */
    public function __construct(string $directory)
    {
        parent::__construct();

        $this
            ->files()
            ->in($directory)
            ->name('/\.php$/')
            ->ignoreDotFiles(false)
            ->exclude([
                '.cache',
                '.local',
                'node_modules',
                'var',
                'vendor',
            ])
        ;
    }
}
