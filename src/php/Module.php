<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use RuntimeException;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;

use function array_all;
use function implode;
use function in_array;
use function sprintf;

/**
 * @internal
 *
 * @phpstan-type ModuleInfo array{
 *     name: string,
 *     packages: array{
 *         requiredAll: list<self::PACKAGE_*>,
 *         optional?: list<self::PACKAGE_*>,
 *     },
 * }
 */
final class Module
{
    public const string PACKAGE_EXTENSION_INSTALLER        = 'phpstan/extension-installer';
    public const string PACKAGE_FINDER                     = 'symfony/finder';
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
    public const string PACKAGE_TYPE_PERFECT               = 'rector/type-perfect';

    public const string NAME_PHP_CS_FIXER = 'phpcsfixer';
    public const string NAME_PHP_STAN     = 'phpstan';
    public const string NAME_RECTOR       = 'rector';

    /**
     * @phpstan-var ModuleInfo
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
     * @phpstan-var ModuleInfo
     */
    public const array MODULE_PHP_STAN = [
        'name'     => self::NAME_PHP_STAN,
        'packages' => [
            'requiredAll' => [
                self::PACKAGE_EXTENSION_INSTALLER,
                self::PACKAGE_PHP_STAN_RULES,
                self::PACKAGE_PHP_STAN_STRICT_RULES,
                self::PACKAGE_PHP_STAN,
                self::PACKAGE_TYPE_PERFECT,
            ],
            'optional' => [
                self::PACKAGE_PHP_STAN_DEPRECATION_RULES,
                self::PACKAGE_PHP_STAN_DOCTRINE,
                self::PACKAGE_PHP_STAN_ERROR_FORMATTER,
                self::PACKAGE_PHP_STAN_PHPUNIT,
                self::PACKAGE_PHP_STAN_SYMFONY,
                self::PACKAGE_PHP_STAN_WEBMOZART_ASSERT,
            ],
        ],
    ];

    /**
     * @phpstan-var ModuleInfo
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
     * @phpstan-var array<self::NAME_*, self::MODULE_*>
     */
    public const array NAME_TO_MODULE_MAP = [
        self::NAME_PHP_CS_FIXER => self::MODULE_PHP_CS_FIXER,
        self::NAME_PHP_STAN     => self::MODULE_PHP_STAN,
        self::NAME_RECTOR       => self::MODULE_RECTOR,
    ];

    private static ComposerJson $composerJson;

    private function __construct() {}

    /**
     * @phpstan-param self::MODULE_* $moduleInfo
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function assertInstalled(array $moduleInfo): void
    {
        $packages = $moduleInfo['packages']['requiredAll'];

        if (array_all($packages, self::isPackageInstalled(...))) {
            return;
        }

        Logger::log('error', sprintf(
            'Failed resolving required dependencies for module "%s". Please install %s.',
            $moduleInfo['name'],
            Str::joinAsQuotedList($packages),
        ));

        Logger::log('info', sprintf('Run `composer req %s` to install.', implode(' ', $packages)));

        throw new RuntimeException('Failed resolving required dependencies.');
    }

    /**
     * @phpstan-param self::PACKAGE_* $package
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function isPackageInstalled(string $package): bool
    {
        self::$composerJson ??= ComposerJson::forProjectUsingThisLibrary();

        return in_array($package, self::$composerJson->getInstalledPackages(), true);
    }
}
