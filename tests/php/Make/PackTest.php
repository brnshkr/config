<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function in_array;
use function sprintf;

/**
 * @internal
 */
#[CoversNothing]
final class PackTest extends TestCase
{
    use MakeTrait;
    use MatchesSnapshots;

    private const string MANIFEST_DIRECTORY = __DIR__ . '/../Fixtures/Make/Manifest';
    private const string PACK_DIRECTORY     = __DIR__ . '/../Fixtures/Make/Pack';

    /**
     * @phpstan-var array<string, list<string>>
     */
    private const array PACKAGES_BY_CONFIG_DIRECTORY = [
        '_BRNSHKR' => ['composer', 'bun'],
        '_JS'      => ['bun'],
        '_PHP'     => ['composer'],
    ];

    public function testAManifestOpeningOnTheNameLineStillNamesThePackage(): void
    {
        $resolved = $this->runMake(['help', 'resolve', 'vv'], directory: self::MANIFEST_DIRECTORY);

        self::assertMatchesRegularExpression('/PACKAGE\s+\?=\s+one-line-manifest/', $resolved);
        self::assertMatchesRegularExpression('/VENDOR\s+\?=\s+@acme/', $resolved);
    }

    public function testTheListTargetsNameWhatEachPackageShips(): void
    {
        $composer = $this->runMake(['composer-list'], directory: self::PACK_DIRECTORY);
        $bun      = $this->runMake(['bun-list'], directory: self::PACK_DIRECTORY);

        self::assertStringContainsString('src/Example.php', $composer);
        self::assertStringNotContainsString('tests/Example.php', $composer);
        self::assertStringContainsString('src/index.mjs', $bun);
        self::assertStringNotContainsString('tests/', $bun);
    }

    #[Group('build')]
    public function testTheFilesEachPackageShips(): void
    {
        $composer = $this->runMake(['composer-list'], directory: self::PROJECT_DIRECTORY);
        $bun      = $this->runMake(['bun-list'], directory: self::PROJECT_DIRECTORY);

        $filesByPackage = [
            'composer' => Str::split(Str::trim($composer), "\n"),
            'bun'      => Str::split(Str::trim($bun), "\n"),
        ];

        foreach (self::getTemplatesWithTheirPackages() as ['template' => $template, 'packages' => $packages]) {
            foreach ($filesByPackage as $package => $files) {
                self::assertSame(
                    in_array($package, $packages, true),
                    in_array($template, $files, true),
                    sprintf('`%s` in the %s package', $template, $package),
                );
            }
        }

        $this->assertMatchesSnapshot(sprintf(
            "=== composer ===\n%s\n\n=== bun ===\n%s\n",
            Str::trim($composer),
            Str::trim($bun),
        ));
    }

    /**
     * @return list<array{
     *     template: string,
     *     packages: list<string>,
     * }>
     */
    private static function getTemplatesWithTheirPackages(): array
    {
        $templateReads = Str::matchAll(
            new Filesystem()->readFile(self::PROJECT_DIRECTORY . '/conf/Makefile'),
            '/\$\((?<directory>_BRNSHKR|_JS|_PHP)_CONFIG_DIR\)\/(?<template>[\w.\-]+)/',
        );

        $stackPartFinder = new Finder()
            ->files()
            ->in(self::PROJECT_DIRECTORY . '/conf')
            ->depth(0)
            ->name(['*.js.dist.*', '*.php.dist.*'])
        ;

        $templatesWithTheirPackages = [];

        foreach ($templateReads as $templateRead) {
            $templatesWithTheirPackages[] = [
                'template' => 'conf/' . ($templateRead['template'] ?? ''),
                'packages' => self::PACKAGES_BY_CONFIG_DIRECTORY[$templateRead['directory'] ?? ''] ?? [],
            ];
        }

        foreach ($stackPartFinder as $stackPart) {
            $templatesWithTheirPackages[] = [
                'template' => 'conf/' . $stackPart->getFilename(),
                'packages' => Str::contains($stackPart->getFilename(), '.js.dist.') ? ['bun'] : ['composer'],
            ];
        }

        return $templatesWithTheirPackages;
    }
}
