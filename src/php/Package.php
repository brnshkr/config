<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use RuntimeException;

use function in_array;

/**
 * @internal
 */
enum Package: string
{
    case DependencyInjection     = 'symfony/dependency-injection';
    case ExtensionInstaller      = 'phpstan/extension-installer';
    case Finder                  = 'symfony/finder';
    case FrameworkBundle         = 'symfony/framework-bundle';
    case Laravel                 = 'laravel/framework';
    case PhpAt                   = 'phpat/phpat';
    case PhpCsFixer              = 'friendsofphp/php-cs-fixer';
    case PhpCsFixerCustomFixers  = 'kubawerlos/php-cs-fixer-custom-fixers';
    case PhpStan                 = 'phpstan/phpstan';
    case PhpStanDeprecationRules = 'phpstan/phpstan-deprecation-rules';
    case PhpStanDoctrine         = 'phpstan/phpstan-doctrine';
    case PhpStanErrorFormatter   = 'ticketswap/phpstan-error-formatter';
    case PhpStanPhpUnit          = 'phpstan/phpstan-phpunit';
    case PhpStanRules            = 'symplify/phpstan-rules';
    case PhpStanStrictRules      = 'phpstan/phpstan-strict-rules';
    case PhpStanSymfony          = 'phpstan/phpstan-symfony';
    case PhpStanWebmozartAssert  = 'phpstan/phpstan-webmozart-assert';
    case Rector                  = 'rector/rector';
    case TwigCsFixer             = 'vincentlanglet/twig-cs-fixer';
    case TypeCoverage            = 'tomasvotruba/type-coverage';

    /**
     * @throws RuntimeException
     */
    public function isInstalled(): bool
    {
        return in_array($this->value, ComposerJson::forProjectUsingThisLibrary()->getInstalledPackages(), true);
    }
}
