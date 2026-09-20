<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use RuntimeException;

use function array_column;
use function array_filter;
use function array_map;
use function array_values;
use function implode;
use function in_array;
use function sprintf;

/**
 * @internal
 */
enum Module: string
{
    case PhpCsFixer  = 'phpcsfixer';
    case PhpStan     = 'phpstan';
    case Rector      = 'rector';
    case TwigCsFixer = 'twigcsfixer';

    /**
     * @return non-empty-list<value-of<self>>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @param list<string> $values
     *
     * @return list<self>
     */
    public static function fromValues(array $values): array
    {
        return $values
            |> (static fn (array $values): array => array_map(self::tryFrom(...), $values))
            |> array_filter(...)
            |> array_values(...);
    }

    /**
     * @return non-empty-list<Package>
     */
    public function getRequiredPackages(): array
    {
        return match ($this) {
            self::PhpCsFixer => [
                Package::Finder,
                Package::PhpCsFixer,
            ],
            self::PhpStan => [
                Package::Finder,
                Package::PhpStan,
            ],
            self::Rector => [
                Package::Finder,
                Package::Rector,
            ],
            self::TwigCsFixer => [
                Package::Finder,
                Package::TwigCsFixer,
            ],
        };
    }

    /**
     * @return list<Package>
     */
    public function getOptionalPackages(): array
    {
        return match ($this) {
            self::PhpCsFixer => [
                Package::PhpCsFixerCustomFixers,
            ],
            self::PhpStan => [
                Package::ExtensionInstaller,
                Package::PhpAt,
                Package::PhpStanDeprecationRules,
                Package::PhpStanDoctrine,
                Package::PhpStanErrorFormatter,
                Package::PhpStanPhpUnit,
                Package::PhpStanRules,
                Package::PhpStanStrictRules,
                Package::PhpStanSymfony,
                Package::PhpStanWebmozartAssert,
                Package::TypeCoverage,
            ],
            self::Rector,
            self::TwigCsFixer => [],
        };
    }

    /**
     * @throws RuntimeException
     */
    public static function warnMissingPackage(Package $package): void
    {
        self::warnMissing([$package], 'Failed resolving required dependency.');
    }

    /**
     * @throws RuntimeException
     */
    public function warnMissingPackages(): void
    {
        self::warnMissing(
            $this->getRequiredPackages(),
            sprintf('Failed resolving required dependencies for module "%s".', $this->value),
        );
    }

    /**
     * @param non-empty-list<Package> $candidates
     *
     * @throws RuntimeException
     */
    private static function warnMissing(array $candidates, string $message): void
    {
        /**
         * @var list<Package> $warnedPackages
         */
        static $warnedPackages = [];

        $packages = array_values(array_filter(
            $candidates,
            static fn (Package $package): bool => !$package->isInstalled()
                && !in_array($package, $warnedPackages, true),
        ));

        if ($packages === []) {
            return;
        }

        $warnedPackages = [...$warnedPackages, ...$packages];
        $packageNames   = array_map(static fn (Package $package): string => $package->value, $packages);

        Logger::log('error', sprintf(
            $message . ' Please install %s.',
            Str::joinAsQuotedList($packageNames),
        ));

        Logger::log('notice', sprintf(
            'Run `%scomposer r --dev %s%s` to install.',
            Logger::ANSI_WHITE_UNDERLINED,
            implode(' ', $packageNames),
            Logger::ANSI_RESET,
        ));
    }
}
