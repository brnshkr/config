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
use RuntimeException;
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

    public function testADevelopmentOnlyPackageIsFoundInTheVendorDirectory(): void
    {
        $vendorDirectory = realpath(__DIR__ . '/Fixtures/ComposerJson/transitive') . '/vendor';

        self::assertSame([
            'acme/direct-dev'     => $vendorDirectory . '/acme/direct-dev',
            'acme/transitive-dev' => $vendorDirectory . '/acme/transitive-dev',
        ], self::fixture('transitive')->getDevelopmentOnlyPackageDirectories());
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

    public function testThePackageNameSplitsIntoItsOrganizationAndItsName(): void
    {
        $composerJson = self::fixture('transitive');

        self::assertSame('acme/root', $composerJson->getPackageFullName());
        self::assertSame('acme', $composerJson->getPackageOrganization());
        self::assertSame('root', $composerJson->getPackageName());
    }

    public function testAManifestWithoutANameHasNoPackageName(): void
    {
        $composerJson = ComposerJson::forPath(__DIR__ . '/Fixtures/ComposerJson/renamed/acme.json');

        self::assertNull($composerJson->getPackageFullName());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Failed to read package name from composer.json file.');

        $composerJson->getPackageName();
    }

    public function testTheTypeAndVersionAreReadWhenTheManifestDeclaresThem(): void
    {
        self::assertSame('library', self::fixture('described')->getPackageType());
        self::assertSame('1.0.0', self::fixture('described')->getPackageVersion());
        self::assertNull(self::fixture('plain')->getPackageType());
    }

    public function testAManifestWithoutAVersionRefusesToNameOne(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Failed to read package version from composer.json file.');

        self::fixture('plain')->getPackageVersion();
    }

    public function testEachRequirementListReadsItsOwnSection(): void
    {
        $composerJson = self::fixture('transitive');

        self::assertSame(['php' => '>=8.5', 'acme/shipped' => '^1.0'], $composerJson->getRequires());
        self::assertSame(['acme/direct-dev' => '^1.0', 'acme/suggested' => '^1.0'], $composerJson->getDevRequires());
        self::assertSame([], self::fixture('described')->getRequires());
    }

    public function testTheDeclaredPackagesJoinBothRequirementLists(): void
    {
        self::assertSame(
            ['php', 'acme/shipped', 'acme/direct-dev', 'acme/suggested'],
            self::fixture('transitive')->getDeclaredPackages(),
        );
    }

    public function testTheNamespacePrefixesAreTheKeysOfTheMap(): void
    {
        self::assertSame(['Acme\\', 'Acme\Tests\\'], self::fixture('transitive')->getNamespacePrefixes());
    }

    public function testTheNamespaceMapJoinsBothAutoloadSections(): void
    {
        self::assertSame(
            ['Acme\\' => 'src/', 'Acme\Tests\\' => 'tests/'],
            self::fixture('transitive')->getNamespaceMap(),
        );
    }

    public function testTheFirstAutoloadDirectoryIsTheFirstPsr4Path(): void
    {
        self::assertSame('src/', self::fixture('transitive')->getFirstAutoloadDirectory());
        self::assertNull(self::fixture('plain')->getFirstAutoloadDirectory());
    }

    public function testTheDirectoryIsTheOneHoldingTheManifest(): void
    {
        self::assertSame(__DIR__ . '/Fixtures/ComposerJson/plain', self::fixture('plain')->getDirectory());
    }

    /**
     * @param non-empty-string $name
     */
    private static function fixture(string $name): ComposerJson
    {
        return ComposerJson::forPath(__DIR__ . '/Fixtures/ComposerJson/' . $name . '/composer.json');
    }
}
