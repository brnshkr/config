<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Json;
use Brnshkr\Config\Str;
use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

use function chdir;
use function dirname;
use function getcwd;
use function realpath;
use function sys_get_temp_dir;

/**
 * @internal
 */
#[CoversClass(ComposerJson::class)]
#[UsesClass(Json::class)]
#[UsesClass(Str::class)]
final class ComposerJsonTest extends TestCase
{
    public function testALookupFromAnotherDirectoryDoesNotReplaceTheOneForThisProject(): void
    {
        $directory    = getcwd() ?: '.';
        $composerJson = ComposerJson::forProjectUsingThisLibrary();

        chdir(__DIR__ . '/Fixtures/ComposerJson/plain');

        try {
            $elsewhere = ComposerJson::forProjectUsingThisLibrary();
        } finally {
            chdir($directory);
        }

        self::assertNotSame($composerJson->path, $elsewhere->path);
        self::assertSame($composerJson->path, ComposerJson::forProjectUsingThisLibrary()->path);
    }

    public function testAPluginApiRequirementProvidesTheWholeComposerNamespace(): void
    {
        self::assertSame(['Composer'], self::fixture('plugin')->getHostProvidedNamespaces());
    }

    public function testARuntimeApiRequirementProvidesOnlyInstalledVersions(): void
    {
        self::assertSame([InstalledVersions::class], self::fixture('runtime')->getHostProvidedNamespaces());
    }

    public function testBothRequirementsProvideBoth(): void
    {
        self::assertSame(['Composer', InstalledVersions::class], self::fixture('both')->getHostProvidedNamespaces());
    }

    public function testADevelopmentRequirementProvidesNothing(): void
    {
        self::assertSame([], self::fixture('plain')->getHostProvidedNamespaces());
    }

    public function testATransitiveDevelopmentOnlyPackageIsForbidden(): void
    {
        self::assertContains('Acme\TransitiveDev', self::fixture('transitive')->getDevelopmentOnlyPackageNamespaces());
    }

    public function testADirectDevelopmentPackageIsForbidden(): void
    {
        self::assertContains('Acme\DirectDev', self::fixture('transitive')->getDevelopmentOnlyPackageNamespaces());
    }

    public function testAShippedPackageIsNotForbidden(): void
    {
        self::assertNotContains('Acme\Shipped', self::fixture('transitive')->getDevelopmentOnlyPackageNamespaces());
    }

    public function testASuggestedPackageIsNotForbidden(): void
    {
        self::assertNotContains('Acme\Suggested', self::fixture('transitive')->getDevelopmentOnlyPackageNamespaces());
    }

    public function testAClassmappedPackageContributesItsTopLevelNamespace(): void
    {
        self::assertContains('Legacy', self::fixture('transitive')->getDevelopmentOnlyPackageNamespaces());
    }

    public function testAClassmappedGlobalClassContributesNoNamespace(): void
    {
        self::assertNotContains('Stringy', self::fixture('transitive')->getDevelopmentOnlyPackageNamespaces());
    }

    public function testTheRootNamespaceComesFromAutoload(): void
    {
        self::assertSame('Acme', self::fixture('transitive')->getRootNamespace());
    }

    public function testDevelopmentNamespacesComeFromAutoloadDev(): void
    {
        self::assertSame(['Acme\Tests'], self::fixture('transitive')->getDevelopmentNamespaces());
    }

    public function testAManifestNotNamedComposerJsonResolvesAgainstItsOwnDirectory(): void
    {
        $directory = __DIR__ . '/Fixtures/ComposerJson/renamed';

        self::assertSame([realpath($directory)], ComposerJson::forPath($directory . '/acme.json')->getDevelopmentDirectories());
    }

    public function testAnAbsoluteVendorDirectoryIsTakenAsIs(): void
    {
        $filesystem = new Filesystem();
        $manifest   = sys_get_temp_dir() . '/brnshkr-absolute-vendor/composer.json';

        $filesystem->dumpFile($manifest, Json::encode([
            'require-dev' => ['acme/direct-dev' => '^1.0'],
            'config'      => ['vendor-dir' => __DIR__ . '/Fixtures/ComposerJson/transitive/vendor'],
        ]));

        try {
            self::assertContains('Acme\DirectDev', ComposerJson::forPath($manifest)->getDevelopmentOnlyPackageNamespaces());
        } finally {
            $filesystem->remove(dirname($manifest));
        }
    }

    /**
     * @param non-empty-string $name
     */
    private static function fixture(string $name): ComposerJson
    {
        return ComposerJson::forPath(__DIR__ . '/Fixtures/ComposerJson/' . $name . '/composer.json');
    }
}
