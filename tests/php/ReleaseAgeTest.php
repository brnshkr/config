<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Composer\ReleaseAge;
use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Str;
use Composer\Package\BasePackage;
use Composer\Package\CompletePackage;
use Composer\Package\RootPackage;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function array_map;

/**
 * @internal
 */
#[CoversClass(ReleaseAge::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(Str::class)]
final class ReleaseAgeTest extends TestCase
{
    private const string NOW = '1991-08-06 12:00:00';

    public function testAVersionYoungerThanTheMinimumAgeIsDropped(): void
    {
        $kept = new ReleaseAge()->getAcceptedPackages([
            self::createPackage('vendor/fresh', '-6 days'),
            self::createPackage('vendor/settled', '-8 days'),
        ], new DateTimeImmutable(self::NOW));

        self::assertSame(['vendor/settled'], self::getNames($kept));
    }

    public function testAVersionWithoutAReleaseTimeIsKept(): void
    {
        $kept = new ReleaseAge()->getAcceptedPackages([self::createPackage('vendor/path', null)], new DateTimeImmutable(self::NOW));

        self::assertSame(['vendor/path'], self::getNames($kept));
    }

    public function testAnExcludedPackageIsKeptHoweverYoung(): void
    {
        $kept = new ReleaseAge(excludedPackageNames: ['symfony/*'])->getAcceptedPackages([
            self::createPackage('symfony/console', '-1 hour'),
            self::createPackage('vendor/fresh', '-1 hour'),
        ], new DateTimeImmutable(self::NOW));

        self::assertSame(['symfony/console'], self::getNames($kept));
    }

    public function testTheRootPackageAndPlatformPackagesAreKept(): void
    {
        $rootPackage = new RootPackage('vendor/root', '1.0.0.0', '1.0.0');

        $rootPackage->setReleaseDate(new DateTimeImmutable(self::NOW));

        $kept = new ReleaseAge()->getAcceptedPackages([
            $rootPackage,
            self::createPackage('php', '-1 hour'),
            self::createPackage('ext-intl', '-1 hour'),
        ], new DateTimeImmutable(self::NOW));

        self::assertSame([
            'vendor/root',
            'php',
            'ext-intl',
        ], self::getNames($kept));
    }

    public function testAMinimumAgeOfZeroKeepsEveryVersion(): void
    {
        $kept = new ReleaseAge(0)->getAcceptedPackages([self::createPackage('vendor/fresh', '-1 second')], new DateTimeImmutable(self::NOW));

        self::assertSame(['vendor/fresh'], self::getNames($kept));
    }

    public function testTheSettingsAreReadFromTheNestedExtraKey(): void
    {
        $releaseAge = self::createReleaseAge([
            'minimum-release-age'          => 3_600,
            'minimum-release-age-excludes' => ['vendor/*'],
        ]);

        $kept = $releaseAge->getAcceptedPackages([
            self::createPackage('other/fresh', '-30 minutes'),
            self::createPackage('other/settled', '-2 hours'),
            self::createPackage('vendor/fresh', '-30 minutes'),
        ], new DateTimeImmutable(self::NOW));

        self::assertSame([
            'other/settled',
            'vendor/fresh',
        ], self::getNames($kept));
    }

    public function testNoSettingsMeansSevenDays(): void
    {
        $kept = self::createReleaseAge([])->getAcceptedPackages([
            self::createPackage('vendor/fresh', '-6 days'),
            self::createPackage('vendor/settled', '-8 days'),
        ], new DateTimeImmutable(self::NOW));

        self::assertSame(['vendor/settled'], self::getNames($kept));
    }

    #[DataProvider('provideAMalformedSettingIsRejectedCases')]
    public function testAMalformedSettingIsRejected(mixed $settings): void
    {
        $this->expectException(RuntimeException::class);

        self::createReleaseAge($settings);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideAMalformedSettingIsRejectedCases(): iterable
    {
        yield 'settings not an object' => ['yes'];

        yield 'negative age' => [['minimum-release-age' => -1]];

        yield 'age as string' => [['minimum-release-age' => '604800']];

        yield 'excludes not a list' => [['minimum-release-age-excludes' => ['a' => 'vendor/*']]];

        yield 'empty exclude' => [['minimum-release-age-excludes' => ['']]];
    }

    public function testADevVersionIsKeptHoweverYoung(): void
    {
        $kept = new ReleaseAge()->getAcceptedPackages([self::createPackage('vendor/branch', '-1 hour', 'dev-main')], new DateTimeImmutable(self::NOW));

        self::assertSame(['vendor/branch'], self::getNames($kept));
    }

    public function testOnlyCheckedVersionsWithoutAReleaseTimeAreListed(): void
    {
        $packages = [
            self::createPackage('vendor/path', null),
            self::createPackage('vendor/branch', null, 'dev-main'),
            self::createPackage('php', null),
        ];

        self::assertSame(['vendor/path'], self::getNames(new ReleaseAge()->getPackagesWithoutReleaseTime($packages)));
        self::assertSame([], new ReleaseAge(0)->getPackagesWithoutReleaseTime($packages));
    }

    public function testTheHeldBackVersionsAreTheOnesDropped(): void
    {
        $packages = [
            self::createPackage('vendor/fresh', '-6 days'),
            self::createPackage('vendor/settled', '-8 days'),
        ];

        self::assertSame(['vendor/fresh'], self::getNames(new ReleaseAge()->getHeldBackPackages($packages, new DateTimeImmutable(self::NOW))));
        self::assertSame([], new ReleaseAge(0)->getHeldBackPackages($packages, new DateTimeImmutable(self::NOW)));
    }

    /**
     * @throws RuntimeException
     */
    private static function createReleaseAge(mixed $settings): ReleaseAge
    {
        $composerJson = ComposerJson::forThisLibrary();
        $organization = $composerJson->getPackageOrganization();
        $name         = $composerJson->getPackageName();

        return ReleaseAge::fromExtra([$organization => [$name => $settings]], $organization, $name);
    }

    private static function createPackage(string $name, ?string $age, string $version = '1.0.0'): CompletePackage
    {
        $completePackage = new CompletePackage($name, $version, $version);

        if ($age !== null) {
            $completePackage->setReleaseDate(new DateTimeImmutable(self::NOW)->modify($age));
        }

        return $completePackage;
    }

    /**
     * @param list<BasePackage> $packages
     *
     * @return list<string>
     */
    private static function getNames(array $packages): array
    {
        return array_map(static fn (BasePackage $basePackage): string => $basePackage->getName(), $packages);
    }
}
