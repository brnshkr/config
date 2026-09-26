<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer;

use Brnshkr\Config\Str;
use Composer\Package\BasePackage;
use Composer\Package\RootPackageInterface;
use Composer\Repository\PlatformRepository;
use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;

use function array_filter;
use function array_is_list;
use function array_values;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;

/**
 * @internal Brnshkr\Config\Composer
 */
final readonly class ReleaseAge
{
    private const int DEFAULT_MINIMUM_AGE      = 604_800;
    private const string KEY_MINIMUM_AGE       = 'minimum-release-age';
    private const string KEY_EXCLUDED_PACKAGES = 'minimum-release-age-excludes';

    private ?string $excludedPackagesPattern;

    /**
     * @param int<0, max> $minimumAge
     * @param list<non-empty-string> $excludedPackageNames
     */
    public function __construct(
        private int $minimumAge = self::DEFAULT_MINIMUM_AGE,
        array $excludedPackageNames = [],
    ) {
        $this->excludedPackagesPattern = $excludedPackageNames === []
            ? null
            : BasePackage::packageNamesToRegexp($excludedPackageNames);
    }

    /**
     * @param array<array-key, mixed> $extra
     *
     * @throws RuntimeException when a setting under `extra.<organization>.<name>` has the wrong type
     */
    public static function fromExtra(array $extra, string $organization, string $name): self
    {
        $organizationSettings = $extra[$organization] ?? [];
        $settings             = is_array($organizationSettings) ? $organizationSettings[$name] ?? [] : null;

        if (!is_array($settings)) {
            throw new RuntimeException(sprintf('The "extra.%s.%s" setting must be an object.', $organization, $name));
        }

        $minimumAge = $settings[self::KEY_MINIMUM_AGE] ?? self::DEFAULT_MINIMUM_AGE;

        if (!is_int($minimumAge) || $minimumAge < 0) {
            throw new RuntimeException(sprintf(
                'The "extra.%s.%s.%s" setting must be a number of seconds, 0 or more.',
                $organization,
                $name,
                self::KEY_MINIMUM_AGE,
            ));
        }

        $excludedPackageNames = $settings[self::KEY_EXCLUDED_PACKAGES] ?? [];

        if (!is_array($excludedPackageNames) || !array_is_list($excludedPackageNames)) {
            throw new RuntimeException(sprintf(
                'The "extra.%s.%s.%s" setting must be a list of package names.',
                $organization,
                $name,
                self::KEY_EXCLUDED_PACKAGES,
            ));
        }

        $excludedPackages = [];

        foreach ($excludedPackageNames as $excludedPackageName) {
            if (!is_string($excludedPackageName) || Str::isEmpty($excludedPackageName)) {
                throw new RuntimeException(sprintf(
                    'The "extra.%s.%s.%s" setting must be a list of package names.',
                    $organization,
                    $name,
                    self::KEY_EXCLUDED_PACKAGES,
                ));
            }

            $excludedPackages[] = $excludedPackageName;
        }

        return new self($minimumAge, $excludedPackages);
    }

    /**
     * @template TPackage of BasePackage
     *
     * @param array<array-key, TPackage> $packages
     *
     * @return list<TPackage>
     */
    public function getAcceptedPackages(array $packages, DateTimeImmutable $now): array
    {
        if ($this->minimumAge === 0) {
            return array_values($packages);
        }

        $cutoffTimestamp = $now->getTimestamp() - $this->minimumAge;

        return array_values(array_filter(
            $packages,
            fn (BasePackage $basePackage): bool => !$this->isTooYoung($basePackage, $cutoffTimestamp),
        ));
    }

    /**
     * @template TPackage of BasePackage
     *
     * @param array<array-key, TPackage> $packages
     *
     * @return list<TPackage>
     */
    public function getHeldBackPackages(array $packages, DateTimeImmutable $now): array
    {
        if ($this->minimumAge === 0) {
            return [];
        }

        $cutoffTimestamp = $now->getTimestamp() - $this->minimumAge;

        return array_values(array_filter(
            $packages,
            fn (BasePackage $basePackage): bool => $this->isTooYoung($basePackage, $cutoffTimestamp),
        ));
    }

    /**
     * @template TPackage of BasePackage
     *
     * @param array<array-key, TPackage> $packages
     *
     * @return list<TPackage>
     */
    public function getPackagesWithoutReleaseTime(array $packages): array
    {
        if ($this->minimumAge === 0) {
            return [];
        }

        return array_values(array_filter(
            $packages,
            fn (BasePackage $basePackage): bool => !$basePackage->getReleaseDate() instanceof DateTimeInterface
                && !$this->isExempt($basePackage),
        ));
    }

    private function isTooYoung(BasePackage $basePackage, int $cutoffTimestamp): bool
    {
        $releaseDate = $basePackage->getReleaseDate();

        return $releaseDate instanceof DateTimeInterface
            && $releaseDate->getTimestamp() > $cutoffTimestamp
            && !$this->isExempt($basePackage);
    }

    private function isExempt(BasePackage $basePackage): bool
    {
        return $basePackage instanceof RootPackageInterface
            || $basePackage->isDev()
            || PlatformRepository::isPlatformPackage($basePackage->getName())
            || ($this->excludedPackagesPattern !== null && Str::match($basePackage->getName(), $this->excludedPackagesPattern) !== []);
    }
}
