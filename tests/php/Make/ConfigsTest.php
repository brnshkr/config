<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\ContainerTrait;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function is_readable;
use function md5_file;
use function sprintf;

/**
 * @internal
 */
#[CoversNothing]
final class ConfigsTest extends TestCase
{
    use ContainerTrait;

    private const string CONFIGS_DIRECTORY  = __DIR__ . '/../Fixtures/Make/Configs';
    private const string TSCONFIG_DIRECTORY = __DIR__ . '/../Fixtures/Make/Typescript';
    private const string FALLBACK_DIRECTORY = __DIR__ . '/../Fixtures/Make/ConfigFallback';
    private const string VENDOR_DIRECTORY   = __DIR__ . '/../Fixtures/Make/ConfigVendor';
    private const string SEARCH_DIRECTORY   = __DIR__ . '/../Fixtures/Make/ConfigSearch';
    private const string GUARD_DIRECTORY    = __DIR__ . '/../Fixtures/Make/Guard';
    private const string COMPOSER_DIRECTORY = __DIR__ . '/../Fixtures/Make/ComposerDirectories';

    #[Before]
    #[After]
    public function removeWhatTheContainerRunsWrote(): void
    {
        $writtenPaths = [
            self::CONFIGS_DIRECTORY . '/conf/php-cs-fixer.dist.php',
            self::CONFIGS_DIRECTORY . '/conf/php-cs-fixer.php',
            self::CONFIGS_DIRECTORY . '/conf/phpstan.dist.php',
            self::CONFIGS_DIRECTORY . '/conf/phpstan.php',
            self::CONFIGS_DIRECTORY . '/conf/phpunit.dist.xml',
            self::CONFIGS_DIRECTORY . '/conf/phpunit.xml',
            self::CONFIGS_DIRECTORY . '/conf/twig-cs-fixer.dist.php',
            self::CONFIGS_DIRECTORY . '/conf/twig-cs-fixer.php',
            self::TSCONFIG_DIRECTORY . '/conf',
            self::TSCONFIG_DIRECTORY . '/tsconfig.json',
        ];

        foreach ([self::CONFIGS_DIRECTORY, self::TSCONFIG_DIRECTORY] as $fixtureDirectory) {
            foreach (['.editorconfig', '.gitattributes', '.gitignore', '.vscode', 'bunfig.toml'] as $writtenName) {
                $writtenPaths[] = $fixtureDirectory . '/' . $writtenName;
            }
        }

        new Filesystem()->remove($writtenPaths);
    }

    public function testMissingConfigurationNamesThePathAndTheVariable(): void
    {
        $result = $this->runMake(['phpstan'], directory: self::GUARD_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('`./conf/phpstan.dist.php` is missing', $result);
        self::assertStringContainsString('PHP_STAN_CONFIG', $result);
    }

    public function testGuardedPathIsTheOneOnThisMachineWhenTheToolsRunElsewhere(): void
    {
        $result = $this->runMake(
            ['phpstan', 'APP_DIR=/app', 'PHP_STAN_CONFIG=/app/conf/phpstan.php'],
            directory: self::GUARD_DIRECTORY,
            doExpectFailure: true,
        );

        self::assertStringContainsString('`./conf/phpstan.php`, which `PHP_STAN_CONFIG` names, is missing', $result);
        self::assertStringNotContainsString('/app/conf/phpstan.php', $result);
    }

    public function testConfigsWritesOnlyWhatTheProjectIsMissing(): void
    {
        $configsDirectory = $this->getFixtureCopy(self::CONFIGS_DIRECTORY);
        $created          = $this->runMake(['configs', 'tools'], directory: $configsDirectory);
        $kept             = $this->runMake(['configs', 'tools'], directory: $configsDirectory);

        self::assertStringContainsString('[acme/configs] Created', $created);
        self::assertStringContainsString('already exists', $kept);
        self::assertStringNotContainsString('Created', $kept);
        self::assertFileExists($configsDirectory . '/conf/phpstan.dist.php');
        self::assertFileDoesNotExist($configsDirectory . '/conf/phpstan.php');
        self::assertFileDoesNotExist($configsDirectory . '/conf/twig-cs-fixer.dist.php');
    }

    public function testTheTypescriptProjectIsWrittenToConfAndLinkedFromTheRoot(): void
    {
        $typescriptDirectory = $this->getFixtureCopy(self::TSCONFIG_DIRECTORY);
        $created             = $this->runMake(['configs'], directory: $typescriptDirectory);
        $kept                = $this->runMake(['configs'], directory: $typescriptDirectory);

        self::assertStringContainsString('Created `./conf/tsconfig.json`.', $created);
        self::assertStringContainsString('Linked `./tsconfig.json` to `./conf/tsconfig.json`.', $created);
        self::assertStringContainsString('`./tsconfig.json` already exists.', $kept);
        self::assertSame('conf/tsconfig.json', new Filesystem()->readlink($typescriptDirectory . '/tsconfig.json'));
    }

    public function testARepositoryWithoutComposerGetsNoExportLines(): void
    {
        $typescriptDirectory = $this->getFixtureCopy(self::TSCONFIG_DIRECTORY);

        $this->runMake(['configs'], directory: $typescriptDirectory);

        $gitattributes = new Filesystem()->readFile($typescriptDirectory . '/.gitattributes');

        self::assertStringNotContainsString('export-ignore', $gitattributes);
        self::assertStringEndsWith("linguist-vendored\n###< brnshkr/config ###\n", $gitattributes);
    }

    public function testAComposerProjectWithoutMarkersGetsTheBlockAheadOfItsOwnLines(): void
    {
        $configsDirectory = $this->getFixtureCopy(self::CONFIGS_DIRECTORY);

        new Filesystem()->dumpFile($configsDirectory . '/.gitattributes', "*.png binary\n");

        $this->runMake(['configs'], directory: $configsDirectory);

        $gitattributes = new Filesystem()->readFile($configsDirectory . '/.gitattributes');

        self::assertStringStartsWith("###> brnshkr/config ###\n", $gitattributes);
        self::assertMatchesRegularExpression('/^\/composer\.json\s+-export-ignore$/m', $gitattributes);
        self::assertStringNotContainsString('/LICENSE', $gitattributes);
        self::assertStringNotContainsString('/README.md', $gitattributes);
        self::assertStringEndsWith("###< brnshkr/config ###\n\n*.png binary\n", $gitattributes);
    }

    public function testTheTypescriptProjectFallsBackToAFileWhereLinkingFails(): void
    {
        $typescriptDirectory = $this->getFixtureCopy(self::TSCONFIG_DIRECTORY);

        $this->runMake(['configs'], ['LN' => 'false'], $typescriptDirectory);

        self::assertStringContainsString(
            '"extends": "./conf/tsconfig.json"',
            new Filesystem()->readFile($typescriptDirectory . '/tsconfig.json'),
        );
    }

    public function testConfigsWritesThePrivateHalfOnlyWhenAskedTo(): void
    {
        foreach (['local', 'l'] as $spelling) {
            $configsDirectory = $this->createFixtureCopy(self::CONFIGS_DIRECTORY);

            $this->runMake(['configs', $spelling], directory: $configsDirectory);

            self::assertFileEquals(
                $configsDirectory . '/conf/phpstan.php.example',
                $configsDirectory . '/conf/phpstan.php',
                $spelling,
            );
        }
    }

    public function testConfigsWritesExactlyTheFileItIsNamed(): void
    {
        $configsDirectory = $this->getFixtureCopy(self::CONFIGS_DIRECTORY);

        $this->runMake(['configs', 'phpstan.php'], directory: $configsDirectory);

        self::assertFileEquals(
            $configsDirectory . '/conf/phpstan.php.example',
            $configsDirectory . '/conf/phpstan.php',
        );

        self::assertFileDoesNotExist($configsDirectory . '/conf/phpstan.dist.php');
        self::assertFileDoesNotExist($configsDirectory . '/.gitignore');
    }

    public function testThePrivateHalfIsWrittenFromATemplateWhenTheProjectHasNone(): void
    {
        $configsDirectory = $this->getFixtureCopy(self::CONFIGS_DIRECTORY);

        $this->runMake(['configs', 'local'], directory: $configsDirectory);

        $shim = new Filesystem()->readFile($configsDirectory . '/conf/php-cs-fixer.php');

        self::assertStringContainsString('$config = include __DIR__ . \'/php-cs-fixer.dist.php\';', $shim);
        self::assertStringContainsString('@internal App', $shim);
    }

    public function testAPrivateHalfThatCannotIncludeIsACopy(): void
    {
        $configsDirectory = $this->getFixtureCopy(self::CONFIGS_DIRECTORY);

        $this->runMake(['configs', 'local'], directory: $configsDirectory);

        self::assertFileEquals(
            $configsDirectory . '/conf/phpunit.dist.xml',
            $configsDirectory . '/conf/phpunit.xml',
        );
    }

    public function testNothingIsWrittenIntoAnInstallationOfThisPackage(): void
    {
        $vendorDirectory = $this->getFixtureCopy(self::VENDOR_DIRECTORY);

        $this->runMake(['configs', 'local'], directory: $vendorDirectory);

        self::assertFileExists($vendorDirectory . '/conf/phpstan.dist.php');
        self::assertFileDoesNotExist($vendorDirectory . '/vendor/' . self::getPackageFullName() . '/conf/phpstan.php');
    }

    public function testThePackageFallbackReadsTheTrackedHalfOnly(): void
    {
        $vendorDirectory = $this->getFixtureCopy(self::VENDOR_DIRECTORY);

        new Filesystem()->touch($vendorDirectory . '/vendor/' . self::getPackageFullName() . '/conf/phpstan.php');

        $resolved = $this->runMake(['help', 'resolve', 'vv'], ['VALUE_WIDTH' => '200'], $vendorDirectory);

        self::assertMatchesRegularExpression(self::getInstalledPhpStanConfigPattern(), $resolved);
    }

    public function testTheConfigAToolReadsIsTheFirstOneThatIsThere(): void
    {
        $configsDirectory = $this->getFixtureCopy(self::CONFIGS_DIRECTORY);
        $resolve          = ['help', 'resolve', 'vv'];

        $this->runMake(['configs'], directory: $configsDirectory);

        $dist = $this->runMake($resolve, ['VALUE_WIDTH' => '200'], $configsDirectory);

        $this->runMake(['configs', 'local'], directory: $configsDirectory);

        $local = $this->runMake($resolve, ['VALUE_WIDTH' => '200'], $configsDirectory);

        self::assertMatchesRegularExpression('/PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.dist\.php/', $dist);
        self::assertMatchesRegularExpression('/PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.php/', $local);

        $pinned = $this->runMake($resolve, ['VALUE_WIDTH' => '200', 'CONFIG' => 'dist'], $configsDirectory);

        self::assertMatchesRegularExpression('/PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.dist\.php/', $pinned);
    }

    public function testAStageReadsItsOwnConfigFirst(): void
    {
        $configsDirectory = $this->getFixtureCopy(self::CONFIGS_DIRECTORY);
        $resolve          = ['help', 'resolve', 'vv', 'VALUE_WIDTH=200'];

        new Filesystem()->touch([
            $configsDirectory . '/.env.prod',
            $configsDirectory . '/conf/phpstan.dist.php',
            $configsDirectory . '/conf/phpstan.php',
            $configsDirectory . '/conf/phpstan.prod.dist.php',
        ]);

        $development = $this->runMake($resolve, directory: $configsDirectory);
        $production  = $this->runMake([...$resolve, 'APP_ENV=prod'], directory: $configsDirectory);
        $local       = $this->runMake([...$resolve, 'APP_ENV=prod', 'CONFIG=local'], directory: $configsDirectory);
        $dist        = $this->runMake([...$resolve, 'APP_ENV=prod', 'CONFIG=dist'], directory: $configsDirectory);

        $this->runMake(['configs', 'gitignore'], directory: $configsDirectory);

        self::assertMatchesRegularExpression('/PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.php/', $development);
        self::assertMatchesRegularExpression('/PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.prod\.dist\.php/', $production);
        self::assertMatchesRegularExpression('/PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.php/', $local);
        self::assertMatchesRegularExpression('/PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.prod\.dist\.php/', $dist);

        self::assertStringContainsString(
            "/conf/phpstan.dev.php\n/conf/phpstan.php\n/conf/phpstan.prod.php\n",
            new Filesystem()->readFile($configsDirectory . '/.gitignore'),
        );
    }

    public function testConfigsWritesWhenThereIsNoRepositoryToAsk(): void
    {
        $configsDirectory = $this->getFixtureCopy(self::CONFIGS_DIRECTORY);

        $result = $this->runMake(
            ['configs'],
            ['GIT_DIR' => '/nonexistent'],
            directory: $configsDirectory,
        );

        self::assertStringContainsString('Created', $result);
    }

    public function testConfigsFailsWhenItCannotWrite(): void
    {
        $configsDirectory = $this->getFixtureCopy(self::CONFIGS_DIRECTORY);

        $result = $this->runMake(
            ['configs', '_CONFIGS=/proc/brnshkr/phpstan.php'],
            directory: $configsDirectory,
            doExpectFailure: true,
        );

        self::assertStringContainsString('Error 69', $result);
        self::assertStringNotContainsString('Created `/proc', $result);
    }

    public function testAMistypedConfigNameIsScoredTheSameWay(): void
    {
        $configsDirectory = $this->getFixtureCopy(self::CONFIGS_DIRECTORY);
        $result           = $this->runMake(['configs', 'phpstna'], directory: $configsDirectory, doExpectFailure: true);

        self::assertStringContainsString('No config named `phpstna`', $result);
        self::assertStringContainsString('Did you mean `phpstan', $result);
    }

    public function testAPrintTargetThatNeedsAFileSaysSoItself(): void
    {
        $eslint    = $this->runMake(['eslint-print'], directory: self::PROJECT_DIRECTORY, doExpectFailure: true);
        $stylelint = $this->runMake(['stylelint-print'], directory: self::PROJECT_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('`eslint-print` needs a file, pass one as an argument.', $eslint);
        self::assertStringContainsString('`stylelint-print` needs a file, pass one as an argument.', $stylelint);
    }

    public function testALoggedPathLinksRelativeToTheProject(): void
    {
        $configsDirectory = self::getRealPath($this->getFixtureCopy(self::CONFIGS_DIRECTORY));

        $result = $this->runMake(['configs'], [
            'EDITOR_URL' => 'acme://open/{cwd}/{file}#{line}',
            ...self::COLORED_ENV,
        ], directory: $configsDirectory);

        self::assertStringContainsString('acme://open/' . $configsDirectory . '/.gitignore#', $result);
        self::assertStringNotContainsString($configsDirectory . '/' . $configsDirectory, $result);
    }

    public function testAConfigIsReadFromTheInstalledPackageWhenTheProjectKeepsNone(): void
    {
        $resolved = $this->runMake(
            ['help', 'resolve', 'vv'],
            ['VALUE_WIDTH' => '200'],
            self::VENDOR_DIRECTORY,
        );

        self::assertMatchesRegularExpression(self::getInstalledPhpStanConfigPattern(), $resolved);
    }

    public function testTheSearchTakesTheMostSpecificDirectoryThatHasAConfig(): void
    {
        $resolved = $this->runMake(
            ['help', 'resolve', 'vv'],
            ['VALUE_WIDTH' => '200'],
            self::SEARCH_DIRECTORY,
        );

        $expected = [
            'PHP_STAN_CONFIG'     => '\.local\/conf\/php\/phpstan\.php',
            'RECTOR_CONFIG'       => '\.local\/rector\.php',
            'PHP_CS_FIXER_CONFIG' => 'conf\/php\/php-cs-fixer\.php',
        ];

        foreach ($expected as $variable => $path) {
            self::assertMatchesRegularExpression(
                '/' . $variable . '\s+\?=\s+\S+' . $path . '/',
                $resolved,
                $variable,
            );
        }
    }

    public function testTheSearchFollowsTheConfiguredDirectories(): void
    {
        $directories = [
            'CACHE_DIR'  => 'acme-cache',
            'CONFIG_DIR' => 'tools',
            'LOCAL_DIR'  => './private',
        ];

        $resolved = $this->runMake(
            ['help', 'resolve', 'vv'],
            [...$directories, 'VALUE_WIDTH' => '200'],
            self::SEARCH_DIRECTORY,
        );

        $exported = $this->runMake(['help', 'env'], $directories, self::SEARCH_DIRECTORY);

        self::assertMatchesRegularExpression('/PHP_STAN_CONFIG\s+\?=\s+\S*(?<!\.local)\/tools\/phpstan\.php/', $resolved);
        self::assertMatchesRegularExpression('/RECTOR_CONFIG\s+\?=\s+\S*\/private\/rector\.php/', $resolved);
        self::assertMatchesRegularExpression('/^\s+BRNSHKR_CONFIG_DIR\s+tools$/m', $exported);
        self::assertMatchesRegularExpression('/^\s+BRNSHKR_CACHE_DIR\s+acme-cache$/m', $exported);
        self::assertMatchesRegularExpression('/^\s+BRNSHKR_LOCAL_DIR\s+private$/m', $exported);
    }

    public function testTheEditorSettingsFollowTheConfiguredDirectories(): void
    {
        $composerDirectory = $this->getFixtureCopy(self::COMPOSER_DIRECTORY);

        $this->runMake(['configs', 'vscode-settings'], ['CACHE_DIR' => 'acme-cache'], $composerDirectory);

        $settings = new Filesystem()->readFile($composerDirectory . '/.vscode/settings.json');

        self::assertStringContainsString('"phpstan.binPath": "./tools/bin/phpstan"', $settings);
        self::assertStringContainsString('"**/acme-cache/**": true,', $settings);
        self::assertStringContainsString('"**/lib/**": true,', $settings);
        self::assertStringNotContainsString('"**/.cache/**"', $settings);
        self::assertStringNotContainsString('"**/vendor/**"', $settings);
    }

    public function testAnExampleComesFromTheOtherInstallationWhenThisOneHasNone(): void
    {
        $fallbackDirectory = $this->getFixtureCopy(self::FALLBACK_DIRECTORY);
        $result            = $this->runMake(['configs', 'tools'], directory: $fallbackDirectory);

        self::assertStringContainsString('Created', $result);
        self::assertFileExists($fallbackDirectory . '/conf/phpstan.dist.php');
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideContainerModeCases')]
    #[Group('container')]
    public function testConfigsWritesTheSameFilesAsANativeRun(string $mode): void
    {
        $this->assertWritesTheSameFilesAsANativeRun($mode, self::CONFIGS_DIRECTORY, ['configs', 'local']);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideContainerModeCases')]
    #[Group('container')]
    public function testTheTypescriptProjectIsLinkedTheSameAsANativeRun(string $mode): void
    {
        $this->assertWritesTheSameFilesAsANativeRun($mode, self::TSCONFIG_DIRECTORY, ['configs']);
    }

    /**
     * @return iterable<string, array{self::MODE_*}>
     */
    public static function provideContainerModeCases(): iterable
    {
        foreach (self::CONTAINER_MODES as $mode) {
            yield $mode => [$mode];
        }
    }

    /**
     * @param self::MODE_* $mode
     * @param list<string> $makeArguments
     */
    private function assertWritesTheSameFilesAsANativeRun(
        string $mode,
        string $fixtureDirectory,
        array $makeArguments,
    ): void {
        $nativeOutput = $this->runMakeIn(self::MODE_NATIVE, $makeArguments, fixtureDirectory: $fixtureDirectory);
        $nativeFiles  = self::describeFiles($fixtureDirectory);

        $this->removeWhatTheContainerRunsWrote();

        $modeOutput = $this->runMakeIn($mode, $makeArguments, fixtureDirectory: $fixtureDirectory);

        self::assertSame($nativeFiles, self::describeFiles($fixtureDirectory));
        self::assertSame($nativeOutput, $modeOutput);
    }

    /**
     * @return list<string>
     */
    private static function describeFiles(string $fixtureDirectory): array
    {
        $descriptions = [];
        $filesystem   = new Filesystem();

        $finder = new Finder()
            ->in($fixtureDirectory)
            ->files()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->sortByName()
        ;

        foreach ($finder as $entry) {
            $entryPath  = $entry->getPathname();
            $linkTarget = $filesystem->readlink($entryPath);

            if ($linkTarget !== null) {
                $descriptions[] = $entry->getRelativePathname() . ': link to ' . $linkTarget;

                continue;
            }

            if (!is_readable($entryPath)) {
                self::fail(sprintf('`%s` was written but cannot be read.', $entry->getRelativePathname()));
            }

            $descriptions[] = $entry->getRelativePathname() . ': ' . md5_file($entryPath);
        }

        return $descriptions;
    }

    private static function getInstalledPhpStanConfigPattern(): string
    {
        return sprintf(
            '/PHP_STAN_CONFIG\s+\?=\s+\S+vendor\/%s\/conf\/phpstan\.dist\.php/',
            Str::quoteRegex(self::getPackageFullName()),
        );
    }

    private static function getPackageFullName(): string
    {
        return ComposerJson::forThisLibrary()->getPackageFullName() ?? self::fail('composer.json names no package.');
    }
}
