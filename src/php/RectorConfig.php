<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\Config\RectorConfig as BaseConfig;
use Rector\Configuration\RectorConfigBuilder;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use RuntimeException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function array_filter;
use function array_map;

Module::assertInstalled(Module::MODULE_RECTOR);

/**
 * @no-named-arguments
 */
final readonly class RectorConfig
{
    private function __construct() {}

    /**
     * @throws DirectoryNotFoundException
     * @throws RuntimeException
     */
    public static function get(string $directory = '.', ?Finder $finder = null): RectorConfigBuilder
    {
        $finder = $finder instanceof Finder
            ? $finder->files()->in($directory)->name('/\.php$/')
            : new PhpFileFinder($directory);

        $paths = array_filter(
            array_map(
                static fn (SplFileInfo $file): string|false => $file->getRealPath(),
                [...$finder],
            ),
            is_string(...),
        );

        /** @disregard P1009 because some skipped classes come bundled with rector and are not picked up by intelephense */
        return BaseConfig::configure()
            ->withCache($directory . '/.cache/rector.cache')
            ->withRootFiles()
            ->withPaths($paths)
            ->withPreparedSets(
                deadCode: true,
                codeQuality: true,
                codingStyle: true,
                typeDeclarations: true,
                privatization: true,
                naming: true,
                instanceOf: true,
                earlyReturn: true,
                rectorPreset: true,
                phpunitCodeQuality: true,
                doctrineCodeQuality: true,
                symfonyCodeQuality: true,
                symfonyConfigs: true,
            )
            ->withSkip([
                LocallyCalledStaticMethodToNonStaticRector::class,
                NewlineBeforeNewAssignSetRector::class,
                NewlineBetweenClassLikeStmtsRector::class,
                NullToStrictStringFuncCallArgRector::class,
                PreferPHPUnitThisCallRector::class,
            ])
            ->withPhpSets()
            ->withAttributesSets()
            ->withImportNames(
                importNames: false,
                importDocBlockNames: false,
                importShortClasses: false,
                removeUnusedImports: true,
            )
        ;
    }
}

return RectorConfig::get();
