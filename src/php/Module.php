<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use RuntimeException;

use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function implode;
use function in_array;
use function is_array;
use function sprintf;

/**
 * @internal
 *
 * @phpstan-type ModuleName key-of<self::MAP>
 * @phpstan-type ModuleInfo self::MODULE_*
 * @phpstan-type _ModuleInfo array{
 *     name: non-empty-string,
 *     packages: array{
 *         requiredAll: non-empty-list<Package>,
 *         optional?: non-empty-list<Package>,
 *     },
 * }
 */
final class Module
{
    public const string NAME_PHP_CS_FIXER  = 'phpcsfixer';
    public const string NAME_PHP_STAN      = 'phpstan';
    public const string NAME_RECTOR        = 'rector';
    public const string NAME_TWIG_CS_FIXER = 'twigcsfixer';

    /**
     * @phpstan-var _ModuleInfo
     */
    public const array MODULE_PHP_CS_FIXER = [
        'name'     => self::NAME_PHP_CS_FIXER,
        'packages' => [
            'requiredAll' => [
                Package::Finder,
                Package::PhpCsFixer,
            ],
            'optional' => [
                Package::PhpCsFixerCustomFixers,
            ],
        ],
    ];

    /**
     * @phpstan-var _ModuleInfo
     */
    public const array MODULE_PHP_STAN = [
        'name'     => self::NAME_PHP_STAN,
        'packages' => [
            'requiredAll' => [
                Package::Finder,
                Package::PhpStan,
            ],
            'optional' => [
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
                Package::TypePerfect,
            ],
        ],
    ];

    /**
     * @phpstan-var _ModuleInfo
     */
    public const array MODULE_RECTOR = [
        'name'     => self::NAME_RECTOR,
        'packages' => [
            'requiredAll' => [
                Package::Finder,
                Package::Rector,
            ],
        ],
    ];

    /**
     * @phpstan-var _ModuleInfo
     */
    public const array MODULE_TWIG_CS_FIXER = [
        'name'     => self::NAME_TWIG_CS_FIXER,
        'packages' => [
            'requiredAll' => [
                Package::Finder,
                Package::TwigCsFixer,
            ],
        ],
    ];

    /**
     * @phpstan-var array<self::NAME_*, ModuleInfo>
     */
    public const array MAP = [
        self::NAME_PHP_CS_FIXER  => self::MODULE_PHP_CS_FIXER,
        self::NAME_PHP_STAN      => self::MODULE_PHP_STAN,
        self::NAME_RECTOR        => self::MODULE_RECTOR,
        self::NAME_TWIG_CS_FIXER => self::MODULE_TWIG_CS_FIXER,
    ];

    /**
     * @var list<Package>
     */
    private static array $warnedPackages = [];

    private function __construct() {}

    /**
     * @param ModuleInfo|Package $moduleInfoOrPackage
     *
     * @throws RuntimeException
     */
    public static function warnMissingPackages(array|Package $moduleInfoOrPackage): void
    {
        $isModuleInfo = is_array($moduleInfoOrPackage);

        $allPackages = $isModuleInfo
            ? $moduleInfoOrPackage['packages']['requiredAll']
            : [$moduleInfoOrPackage];

        $packages = array_values(array_filter(
            $allPackages,
            static fn (Package $package): bool => !$package->isInstalled()
                && !in_array($package, self::$warnedPackages, true),
        ));

        if ($packages === []) {
            return;
        }

        self::$warnedPackages = array_merge(self::$warnedPackages, $packages);
        $packageNames         = array_map(static fn (Package $package): string => $package->value, $packages);

        $message = $isModuleInfo
            ? sprintf('Failed resolving required dependencies for module "%s".', $moduleInfoOrPackage['name'])
            : 'Failed resolving required dependency.';

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
