<?php

/**
 * @api
 */

declare(strict_types=1);

namespace Brnshkr\Config;

use PhpCsFixer\Config as PhpCsFixerConfig;
use PhpCsFixerCustomFixers\Fixer;
use PhpCsFixerCustomFixers\Fixers;
use RuntimeException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

use function array_fill_keys;
use function array_merge;

Module::warnMissingPackages(Module::MODULE_PHP_CS_FIXER);

/**
 * Builds a ready-to-use PHP-CS-Fixer config that captures the @brnshkr coding-style decisions.
 *
 * Extends the `@PhpCsFixer` and `@PhpCsFixer:risky` presets with project-specific opinions
 * around alignment, ordering, native-function invocation, and PHPDoc layout. When the optional
 * `kubawerlos/php-cs-fixer-custom-fixers` package is installed, its fixers are layered on top
 * automatically.
 *
 * @no-named-arguments
 */
final readonly class PhpCsFixer
{
    private function __construct() {}

    /**
     * Build a fully configured php-cs-fixer Config.
     *
     * Caller may pass a Finder to narrow scope (e.g. lint a single subdirectory); otherwise
     * the project-wide {@see FileFinder} defaults apply.
     *
     * @example
     * ```php
     * // conf/php-cs-fixer.php
     * return PhpCsFixer::getConfig();
     * ```
     *
     * @param ?Finder $finder Pre-configured Finder to extend, or null for project defaults
     *
     * @return PhpCsFixerConfig Configured Config instance ready for php-cs-fixer
     *
     * @throws DirectoryNotFoundException When FileFinder cannot resolve the source directory
     * @throws RuntimeException When required php-cs-fixer dependencies are missing
     */
    public static function getConfig(?Finder $finder = null): PhpCsFixerConfig
    {
        $config = new PhpCsFixerConfig();

        $config
            ->setCacheFile('.cache/php-cs-fixer.cache.json')
            ->setFinder(FileFinder::get($finder))
            ->setRiskyAllowed(true)
        ;

        $rules = [
            '@auto:risky'                       => true,
            '@PhpCsFixer'                       => true,
            '@PhpCsFixer:risky'                 => true,
            'binary_operator_spaces'            => ['operators' => array_fill_keys(['=', '=>', '??=', '.=', '+=', '-=', '*=', '/=', '%=', '**=', '&=', '|=', '^=', '<<=', '>>='], 'align_single_space_minimal')],
            'blank_line_before_statement'       => ['statements' => ['break', 'case', 'continue', 'declare', 'default', 'do', 'exit', 'for', 'foreach', 'goto', 'if', 'include', 'include_once', 'phpdoc', 'require', 'require_once', 'return', 'switch', 'throw', 'try', 'while', 'yield', 'yield_from']],
            'class_attributes_separation'       => ['elements' => ['case' => 'none', 'method' => 'one', 'property' => 'one', 'trait_import' => 'none']],
            'class_definition'                  => ['multi_line_extends_each_single_line' => true, 'single_item_single_line' => true],
            'comment_to_phpdoc'                 => ['ignored_tags' => ['phpstan-ignore', 'phpstan-ignore-line', 'phpstan-ignore-next-line']],
            'concat_space'                      => ['spacing' => 'one'],
            'date_time_create_from_format_call' => true,
            'date_time_immutable'               => true,
            'declare_strict_types'              => true,
            'final_class'                       => true,
            'fully_qualified_strict_types'      => ['import_symbols' => true, 'leading_backslash_in_global_namespace' => true],
            'get_class_to_class_keyword'        => true,
            'global_namespace_import'           => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
            'increment_style'                   => ['style' => 'post'],
            'mb_str_functions'                  => true,
            'modernize_strpos'                  => true,
            'multiline_promoted_properties'     => true,
            'native_constant_invocation'        => true,
            'native_function_invocation'        => ['include' => ['@all']],
            'ordered_interfaces'                => true,
            'ordered_types'                     => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
            'phpdoc_align'                      => ['align' => 'left'],
            'phpdoc_array_type'                 => true,
            'phpdoc_line_span'                  => true,
            'phpdoc_list_type'                  => true,
            'phpdoc_order_by_value'             => ['annotations' => ['author', 'covers', 'coversNothing', 'dataProvider', 'depends', 'group', 'method', 'mixin', 'property', 'property-read', 'property-write', 'requires', 'throws', 'uses']],
            'phpdoc_separation'                 => ['groups' => [['filesource'], ['api', 'internal'], ['ignore'], ['deprecated', 'since', 'todo', 'version'], ['author', 'copyright', 'license'], ['category', 'package', 'subpackage'], ['phpstan-import-type'], ['uses'], ['name'], ['method'], ['property', 'phpstan-property', 'property-read', 'phpstan-property-read', 'property-write', 'phpstan-property-write'], ['abstract', 'access', 'final', 'static'], ['global', 'staticvar', 'var'], ['param', 'phpstan-param'], ['return', 'phpstan-return'], ['throws'], ['phpstan-assert', 'phpstan-assert-if-true', 'phpstan-assert-if-false'], ['link', 'see', 'tutorial'], ['example']]],
            'phpdoc_to_comment'                 => ['allow_before_return_statement' => true, 'ignored_tags' => ['disregard']],
            'phpdoc_types_order'                => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
            'php_unit_internal_class'           => ['types' => ['abstract', 'final', 'normal']],
            'psr_autoloading'                   => ['dir' => ComposerJson::forProjectUsingThisLibrary()->getFirstAutoloadDirectory()],
            'regular_callable_call'             => true,
            'return_assignment'                 => ['skip_named_var_tags' => true],
            'self_static_accessor'              => true,
            'simplified_if_return'              => true,
            'single_quote'                      => ['strings_containing_single_quote_chars' => true],
            'standardize_increment'             => false,
            'static_lambda'                     => true,
            'strict_comparison'                 => true,
            'strict_param'                      => true,
            'trailing_comma_in_multiline'       => ['elements' => ['arrays', 'array_destructuring', 'arguments', 'match', 'parameters']],
            'unary_operator_spaces'             => ['only_dec_inc' => false],
            'yoda_style'                        => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        ];

        if (Module::isPackageInstalled(Module::PACKAGE_PHP_CS_FIXER_CUSTOM_FIXERS)) {
            $config->registerCustomFixers(new Fixers());

            $rules = array_merge($rules, [
                Fixer\ForeachUseValueFixer::name()                      => true,
                Fixer\MultilineCommentOpeningClosingAloneFixer::name()  => true,
                Fixer\NoDoctrineMigrationsGeneratedCommentFixer::name() => true,
                Fixer\NoDuplicatedArrayKeyFixer::name()                 => true,
                Fixer\NoDuplicatedImportsFixer::name()                  => true,
                Fixer\NoUselessCommentFixer::name()                     => true,
                Fixer\NoUselessDirnameCallFixer::name()                 => true,
                Fixer\NoUselessDoctrineRepositoryCommentFixer::name()   => true,
                Fixer\NoUselessWriteVisibilityFixer::name()             => true,
                Fixer\NoUselessStrlenFixer::name()                      => true,
                Fixer\PhpdocNoIncorrectVarAnnotationFixer::name()       => true,
                Fixer\PhpdocSelfAccessorFixer::name()                   => true,
                Fixer\PhpdocTypesCommaSpacesFixer::name()               => true,
                Fixer\PhpUnitAssertArgumentsOrderFixer::name()          => true,
                Fixer\PhpUnitDedicatedAssertFixer::name()               => true,
                Fixer\PhpUnitNoUselessReturnFixer::name()               => true,
                Fixer\PromotedConstructorPropertyFixer::name()          => true,
                Fixer\StringableInterfaceFixer::name()                  => true,
                Fixer\TrimKeyFixer::name()                              => true,
                Fixer\TypedClassConstantFixer::name()                   => true,
            ]);
        }

        $config->setRules($rules);

        return $config;
    }
}

return PhpCsFixer::getConfig();
