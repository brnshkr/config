<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\ComposerJson;
use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ComposerJson::class)]
final class ComposerJsonTest extends TestCase
{
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

    /**
     * @param non-empty-string $name
     */
    private static function fixture(string $name): ComposerJson
    {
        return ComposerJson::forPath(__DIR__ . '/Fixtures/ComposerJson/' . $name . '/composer.json');
    }
}
