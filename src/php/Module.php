<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use RuntimeException;

use function array_all;
use function array_diff;
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
 * @phpstan-type PackageName self::PACKAGE_*
 * @phpstan-type _ModuleInfo array{
 *     name: non-empty-string,
 *     packages: array{
 *         requiredAll: non-empty-list<PackageName>,
 *         optional?: non-empty-list<PackageName>,
 *     },
 * }
 */
final class Module
{
    public const string PACKAGE_EXTENSION_INSTALLER        = 'phpstan/extension-installer';
    public const string PACKAGE_FINDER                     = 'symfony/finder';
    public const string PACKAGE_PHP_AT                     = 'phpat/phpat';
    public const string PACKAGE_PHP_CS_FIXER               = 'friendsofphp/php-cs-fixer';
    public const string PACKAGE_PHP_CS_FIXER_CUSTOM_FIXERS = 'kubawerlos/php-cs-fixer-custom-fixers';
    public const string PACKAGE_PHP_STAN_DEPRECATION_RULES = 'phpstan/phpstan-deprecation-rules';
    public const string PACKAGE_PHP_STAN_DOCTRINE          = 'phpstan/phpstan-doctrine';
    public const string PACKAGE_PHP_STAN_PHPUNIT           = 'phpstan/phpstan-phpunit';
    public const string PACKAGE_PHP_STAN_RULES             = 'symplify/phpstan-rules';
    public const string PACKAGE_PHP_STAN_STRICT_RULES      = 'phpstan/phpstan-strict-rules';
    public const string PACKAGE_PHP_STAN_SYMFONY           = 'phpstan/phpstan-symfony';
    public const string PACKAGE_PHP_STAN_WEBMOZART_ASSERT  = 'phpstan/phpstan-webmozart-assert';
    public const string PACKAGE_PHP_STAN                   = 'phpstan/phpstan';
    public const string PACKAGE_RECTOR                     = 'rector/rector';
    public const string PACKAGE_PHP_STAN_ERROR_FORMATTER   = 'ticketswap/phpstan-error-formatter';
    public const string PACKAGE_TWIG_CS_FIXER              = 'vincentlanglet/twig-cs-fixer';
    public const string PACKAGE_TYPE_PERFECT               = 'rector/type-perfect';

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
                self::PACKAGE_FINDER,
                self::PACKAGE_PHP_CS_FIXER,
            ],
            'optional' => [
                self::PACKAGE_PHP_CS_FIXER_CUSTOM_FIXERS,
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
                self::PACKAGE_FINDER,
                self::PACKAGE_PHP_STAN,
            ],
            'optional' => [
                self::PACKAGE_EXTENSION_INSTALLER,
                self::PACKAGE_PHP_AT,
                self::PACKAGE_PHP_STAN_DEPRECATION_RULES,
                self::PACKAGE_PHP_STAN_DOCTRINE,
                self::PACKAGE_PHP_STAN_ERROR_FORMATTER,
                self::PACKAGE_PHP_STAN_PHPUNIT,
                self::PACKAGE_PHP_STAN_RULES,
                self::PACKAGE_PHP_STAN_STRICT_RULES,
                self::PACKAGE_PHP_STAN_SYMFONY,
                self::PACKAGE_PHP_STAN_WEBMOZART_ASSERT,
                self::PACKAGE_TYPE_PERFECT,
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
                self::PACKAGE_FINDER,
                self::PACKAGE_RECTOR,
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
                self::PACKAGE_FINDER,
                self::PACKAGE_TWIG_CS_FIXER,
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

    private static ComposerJson $composerJson;

    /**
     * @var list<PackageName>
     */
    private static array $warnedPackages = [];

    private function __construct() {}

    /**
     * @param ModuleInfo|PackageName $moduleInfoOrPackage
     *
     * @throws RuntimeException
     */
    public static function warnMissingPackages(array|string $moduleInfoOrPackage): void
    {
        $isModuleInfo = is_array($moduleInfoOrPackage);

        $allPackages = $isModuleInfo
            ? $moduleInfoOrPackage['packages']['requiredAll']
            : [$moduleInfoOrPackage];

        if (array_all($allPackages, self::isPackageInstalled(...))) {
            return;
        }

        $packages = array_values(array_diff($allPackages, self::$warnedPackages));

        if ($packages === []) {
            return;
        }

        self::$warnedPackages = array_merge(self::$warnedPackages, $packages);

        $message = $isModuleInfo
            ? sprintf('Failed resolving required dependencies for module "%s".', $moduleInfoOrPackage['name'])
            : 'Failed resolving required dependency.';

        Logger::log('error', sprintf(
            $message . ' Please install %s.',
            Str::joinAsQuotedList($packages),
        ));

        Logger::log('notice', sprintf(
            'Run `%scomposer r --dev %s%s` to install.',
            Logger::ANSI_WHITE_UNDERLINED,
            implode(' ', $packages),
            Logger::ANSI_RESET,
        ));
    }

    /**
     * @param PackageName $package
     *
     * @throws RuntimeException
     */
    public static function isPackageInstalled(string $package): bool
    {
        self::$composerJson ??= ComposerJson::forProjectUsingThisLibrary();

        return in_array($package, self::$composerJson->getInstalledPackages(), true);
    }
}
