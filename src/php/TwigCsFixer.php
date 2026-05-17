<?php

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

Module::warnMissingPackages(Module::MODULE_TWIG_CS_FIXER);

/**
 * Builds a ready-to-use Twig-CS-Fixer config that captures the @brnshkr template-style decisions.
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class TwigCsFixer
{
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
     * @param ?Finder $finder Pre-configured Finder to extend, or null for project defaults
     *
     * @return TwigCsFixerConfig Configured Config instance ready for twig-cs-fixer
     *
     * @throws DirectoryNotFoundException When FileFinder cannot resolve the source directory
     */
    public static function getConfig(?Finder $finder = null): TwigCsFixerConfig
    {
        $config = new TwigCsFixerConfig()
            ->setCacheFile('.cache/twig-cs-fixer.cache.json')
            ->setFinder(FileFinder::get($finder, FileFinder::EXTENSION_TWIG))
            ->allowNonFixableRules()
        ;

        $config->getRuleset()
            ->removeRule(IncludeFunctionRule::class)
            ->addRule(new FileExtensionRule())
            ->addRule(new ValidConstantFunctionRule())
            ->addRule(new FileNameRule(FileNameRule::KEBAB_CASE, optionalPrefix: '_'))
            ->addRule(new DirectoryNameRule(DirectoryNameRule::KEBAB_CASE))
            ->overrideRule(new NamedArgumentNameRule(NamedArgumentNameRule::CAMEL_CASE))
            ->overrideRule(new MacroArgumentNameRule(MacroArgumentNameRule::CAMEL_CASE))
            ->overrideRule(new CompactHashRule(compact: true))
        ;

        return $config;
    }
}

return TwigCsFixer::getConfig();
