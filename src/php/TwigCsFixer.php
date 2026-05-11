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
 * @api
 *
 * @no-named-arguments
 */
final readonly class TwigCsFixer
{
    private function __construct() {}

    /**
     * @throws DirectoryNotFoundException
     */
    public static function getConfig(?Finder $finder = null): TwigCsFixerConfig
    {
        $config = (new TwigCsFixerConfig())
            ->setCacheFile('.cache/twig-cs-fixer.cache.json')
            ->setFinder(FileFinder::get($finder, FileFinder::EXTENSION_TWIG))
            ->allowNonFixableRules()
        ;

        $config->getRuleset()
            ->removeRule(IncludeFunctionRule::class)
            ->addRule(new FileExtensionRule())
            ->addRule(new ValidConstantFunctionRule())
            ->addRule(new FileNameRule(FileNameRule::KEBAB_CASE))
            ->addRule(new DirectoryNameRule(DirectoryNameRule::KEBAB_CASE))
            ->overrideRule(new NamedArgumentNameRule(NamedArgumentNameRule::CAMEL_CASE))
            ->overrideRule(new MacroArgumentNameRule(MacroArgumentNameRule::CAMEL_CASE))
            ->overrideRule(new CompactHashRule(compact: true))
        ;

        return $config;
    }
}

return TwigCsFixer::getConfig();
