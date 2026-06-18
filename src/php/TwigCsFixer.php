<?php

/**
 * @api
 */

declare(strict_types=1);

namespace Brnshkr\Config;

use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use TwigCsFixer\Config\Config as TwigCsFixerConfig;
use TwigCsFixer\Rules\File\DirectoryNameRule;
use TwigCsFixer\Rules\File\FileExtensionRule;
use TwigCsFixer\Rules\File\FileNameRule;
use TwigCsFixer\Rules\Function\IncludeFunctionRule;
use TwigCsFixer\Rules\Function\MacroArgumentNameRule;
use TwigCsFixer\Rules\Function\NamedArgumentNameRule;
use TwigCsFixer\Rules\Literal\CompactHashRule;
use TwigCsFixer\Rules\Node\ValidConstantFunctionRule;

use function preg_quote;
use function sprintf;

Module::warnMissingPackages(Module::MODULE_TWIG_CS_FIXER);

/**
 * Builds a ready-to-use Twig-CS-Fixer config that captures the @brnshkr template-style decisions.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/TwigCsFixer.md
 *
 * @no-named-arguments
 */
final readonly class TwigCsFixer
{
    private const string BUNDLES_DIRECTORY    = 'templates/bundles';
    private const string COMPONENTS_DIRECTORY = 'templates/components';

    private function __construct() {}

    /**
     * Build a fully configured twig-cs-fixer Config.
     *
     * Caller may pass a Finder to narrow scope; otherwise the project-wide {@see FileFinder}
     * defaults apply (Twig extension only).
     *
     * @example
     * ```php
     * // conf/twig-cs-fixer.php
     * return TwigCsFixer::getConfig();
     * ```
     *
     * @param ?Finder $finder pre-configured Finder to extend, or null for project defaults
     *
     * @return TwigCsFixerConfig configured Config instance ready for twig-cs-fixer
     *
     * @throws DirectoryNotFoundException when FileFinder cannot resolve the source directory
     */
    public static function getConfig(?Finder $finder = null): TwigCsFixerConfig
    {
        $config = new TwigCsFixerConfig()
            ->allowNonFixableRules()
            ->setCacheFile('.cache/twig-cs-fixer.cache.json')
            ->setFinder(FileFinder::get($finder, FileFinder::EXTENSION_TWIG)->notPath(sprintf(
                '/^%s\/[^\/]+\/[^\/]+\//',
                preg_quote(self::BUNDLES_DIRECTORY, '/'),
            )))
        ;

        $config->getRuleset()
            ->removeRule(IncludeFunctionRule::class)
            ->addRule(new DirectoryNameRule(
                case: DirectoryNameRule::KEBAB_CASE,
                ignoredSubDirectories: [self::BUNDLES_DIRECTORY, self::COMPONENTS_DIRECTORY],
            ))
            ->addRule(new DirectoryNameRule(
                case: DirectoryNameRule::PASCAL_CASE,
                baseDirectory: self::BUNDLES_DIRECTORY,
            ))
            ->addRule(new DirectoryNameRule(
                case: DirectoryNameRule::PASCAL_CASE,
                baseDirectory: self::COMPONENTS_DIRECTORY,
            ))
            ->addRule(new FileExtensionRule())
            ->addRule(new FileNameRule(
                case: FileNameRule::KEBAB_CASE,
                ignoredSubDirectories: [self::BUNDLES_DIRECTORY, self::COMPONENTS_DIRECTORY],
                optionalPrefix: '_',
            ))
            ->addRule(new FileNameRule(
                case: FileNameRule::PASCAL_CASE,
                baseDirectory: self::COMPONENTS_DIRECTORY,
            ))
            ->addRule(new ValidConstantFunctionRule())
            ->overrideRule(new CompactHashRule(compact: true))
            ->overrideRule(new MacroArgumentNameRule(MacroArgumentNameRule::CAMEL_CASE))
            ->overrideRule(new NamedArgumentNameRule(NamedArgumentNameRule::CAMEL_CASE))
        ;

        return $config;
    }
}

return TwigCsFixer::getConfig();
