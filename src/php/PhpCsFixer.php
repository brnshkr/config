<?php

/**
 * @api
 */

declare(strict_types=1);

namespace Brnshkr\Config;

use Brnshkr\Config\Tests\PhpCsFixerTest;
use PhpCsFixer\Config as PhpCsFixerConfig;
use PhpCsFixerCustomFixers\Fixer;
use PhpCsFixerCustomFixers\Fixers;
use RuntimeException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

use function array_diff_key;
use function array_fill_keys;
use function array_filter;
use function array_merge;

use const ARRAY_FILTER_USE_KEY;

// @codeCoverageIgnoreStart
// NOTICE: Ignored so a random test is not charged with this file-level statement
Module::PhpCsFixer->warnMissingPackages();
// @codeCoverageIgnoreEnd

/**
 * Builds a ready-to-use PHP-CS-Fixer config that captures the @brnshkr coding-style decisions.
 *
 * Extends the `@PhpCsFixer` and `@PhpCsFixer:risky` presets with project-specific opinions
 * around alignment, ordering, native-function invocation, and PHPDoc layout. When the optional
 * `kubawerlos/php-cs-fixer-custom-fixers` package is installed, its fixers are layered on top
 * automatically.
 *
 * @no-named-arguments
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/PhpCsFixer.md
 * @see PhpCsFixerTest
 */
final readonly class PhpCsFixer
{
    private function __construct(
        private PhpCsFixerConfig $phpCsFixerConfig,
    ) {}

    /**
     * Build a fully configured php-cs-fixer Config.
     *
     * Caller may pass a Finder to narrow scope (e.g. lint a single subdirectory); otherwise
     * the project-wide {@see FileFinder} defaults apply.
     *
     * @param ?Finder $finder pre-configured Finder to extend, or null for project defaults
     *
     * @return PhpCsFixerConfig configured Config instance ready for php-cs-fixer
     *
     * @throws DirectoryNotFoundException when FileFinder cannot resolve the source directory
     * @throws RuntimeException when required php-cs-fixer dependencies are missing
     *
     * @example
     * ```php
     * // conf/php-cs-fixer.php
     * return PhpCsFixer::getConfig();
     * ```
     */
    public static function getConfig(?Finder $finder = null): PhpCsFixerConfig
    {
        return self::getBuilder($finder)->build();
    }

    /**
     * A config another file already built, as a builder to add to.
     *
     * This is what a private `conf/php-cs-fixer.php` reaches for: the tracked config it includes stays
     * the baseline, and every verb here adds to it rather than replacing what that file configured.
     *
     * @param PhpCsFixerConfig $phpCsFixerConfig config to extend
     *
     * @return self the builder, wrapping that config
     *
     * @example
     * ```php
     * // conf/php-cs-fixer.php
     * $config = include __DIR__ . '/php-cs-fixer.dist.php';
     *
     * return PhpCsFixer::from($config)
     *     ->addRules(['simplified_null_return' => true])
     *     ->build()
     * ;
     * ```
     */
    public static function from(PhpCsFixerConfig $phpCsFixerConfig): self
    {
        return new self($phpCsFixerConfig);
    }

    /**
     * The same configuration as {@see self::getConfig()}, as a builder to extend before
     * {@see self::build()} finalizes it.
     *
     * @param ?Finder $finder pre-configured Finder to extend, or null for project defaults
     *
     * @return self the builder, pre-configured with the baseline
     *
     * @throws DirectoryNotFoundException when FileFinder cannot resolve the source directory
     * @throws RuntimeException when required php-cs-fixer dependencies are missing
     *
     * @example
     * ```php
     * // conf/php-cs-fixer.php
     * return PhpCsFixer::getBuilder()
     *     ->addRules(['simplified_null_return' => true])
     *     ->build()
     * ;
     * ```
     */
    public static function getBuilder(?Finder $finder = null): self
    {
        $config = new PhpCsFixerConfig();

        $config
            ->setCacheFile('.cache/php-cs-fixer.cache.json')
            ->setFinder(FileFinder::get($finder))
            ->setRiskyAllowed(true)
        ;

        $rules = [
            '@auto:risky'            => true,
            '@PhpCsFixer'            => true,
            '@PhpCsFixer:risky'      => true,
            'binary_operator_spaces' => [
                'operators' => array_fill_keys([
                    '=',
                    '=>',
                    '??=',
                    '.=',
                    '+=',
                    '-=',
                    '*=',
                    '/=',
                    '%=',
                    '**=',
                    '&=',
                    '|=',
                    '^=',
                    '<<=',
                    '>>=',
                ], 'align_single_space_minimal'),
            ],
            'blank_line_before_statement' => [
                'statements' => [
                    'break',
                    'case',
                    'continue',
                    'declare',
                    'default',
                    'do',
                    'exit',
                    'for',
                    'foreach',
                    'goto',
                    'if',
                    'include',
                    'include_once',
                    'phpdoc',
                    'require',
                    'require_once',
                    'return',
                    'switch',
                    'throw',
                    'try',
                    'while',
                    'yield',
                    'yield_from',
                ],
            ],
            'class_attributes_separation' => [
                'elements' => [
                    'case'         => 'none',
                    'method'       => 'one',
                    'property'     => 'one',
                    'trait_import' => 'none',
                ],
            ],
            'class_definition' => [
                'multi_line_extends_each_single_line' => true,
                'single_item_single_line'             => true,
            ],
            'comment_to_phpdoc' => [
                'ignored_tags' => [
                    'codeCoverageIgnore',
                    'codeCoverageIgnoreEnd',
                    'codeCoverageIgnoreStart',
                    'phpstan-ignore',
                    'phpstan-ignore-line',
                    'phpstan-ignore-next-line',
                ],
            ],
            'concat_space' => [
                'spacing' => 'one',
            ],
            'date_time_create_from_format_call' => true,
            'date_time_immutable'               => true,
            'declare_strict_types'              => true,
            'final_class'                       => true,
            'fully_qualified_strict_types'      => [
                'import_symbols'                        => true,
                'leading_backslash_in_global_namespace' => true,
            ],
            'get_class_to_class_keyword' => true,
            'global_namespace_import'    => [
                'import_classes'   => true,
                'import_constants' => true,
                'import_functions' => true,
            ],
            'increment_style' => [
                'style' => 'post',
            ],
            'mb_str_functions'              => true,
            'modernize_strpos'              => true,
            'multiline_promoted_properties' => true,
            'native_constant_invocation'    => true,
            'native_function_invocation'    => [
                'include' => [
                    '@all',
                ],
            ],
            'numeric_literal_separator' => [
                'override_existing' => true,
            ],
            'ordered_interfaces' => true,
            'ordered_types'      => [
                'null_adjustment' => 'always_last',
                'sort_algorithm'  => 'none',
            ],
            'phpdoc_align' => [
                'align' => 'left',
            ],
            'phpdoc_array_type' => true,
            'phpdoc_line_span'  => true,
            'phpdoc_list_type'  => true,
            'phpdoc_order'      => [
                'order' => [
                    'filesource',
                    'api',
                    'internal',
                    'named-arguments',
                    'no-named-arguments',
                    'deprecated',
                    'ignore',
                    'since',
                    'version',
                    'todo',
                    'author',
                    'copyright',
                    'license',
                    'category',
                    'package',
                    'subpackage',
                    'type',
                    'import-type',
                    'template',
                    'template-covariant',
                    'template-contravariant',
                    'extends',
                    'implements',
                    'use',
                    'require-extends',
                    'require-implements',
                    'consistent-constructor',
                    'immutable',
                    'pure',
                    'impure',
                    'readonly',
                    'readonly-allow-private-mutation',
                    'allow-private-mutation',
                    'final',
                    'global',
                    'abstract',
                    'static',
                    'access',
                    'staticvar',
                    'var',
                    'uses',
                    'name',
                    'method',
                    'property',
                    'property-read',
                    'property-write',
                    'param',
                    'param-out',
                    'param-immediately-invoked-callable',
                    'param-later-invoked-callable',
                    'param-closure-this',
                    'return',
                    'throws',
                    'assert',
                    'assert-if-true',
                    'assert-if-false',
                    'self-out',
                    'this-out',
                    'link',
                    'see',
                    'tutorial',
                    'example',
                ],
            ],
            'phpdoc_order_by_value' => [
                'annotations' => [
                    'author',
                    'covers',
                    'coversNothing',
                    'dataProvider',
                    'depends',
                    'group',
                    'method',
                    'mixin',
                    'property',
                    'property-read',
                    'property-write',
                    'requires',
                    'throws',
                    'uses',
                ],
            ],
            'phpdoc_separation' => [
                'groups' => [
                    [
                        'filesource',
                    ],
                    [
                        'api',
                        'internal',
                    ],
                    [
                        'named-arguments',
                        'no-named-arguments',
                    ],
                    [
                        'deprecated',
                        'ignore',
                        'since',
                        'version',
                        'todo',
                    ],
                    [
                        'author',
                        'copyright',
                        'license',
                    ],
                    [
                        'category',
                        'package',
                        'subpackage',
                    ],
                    [
                        'phpstan-type',
                        'phpstan-import-type',
                    ],
                    [
                        'template',
                        'phpstan-template',
                        'template-covariant',
                        'phpstan-template-covariant',
                        'template-contravariant',
                        'phpstan-template-contravariant',
                    ],
                    [
                        'extends',
                        'phpstan-extends',
                        'implements',
                        'phpstan-implements',
                        'use',
                        'phpstan-use',
                    ],
                    [
                        'require-extends',
                        'phpstan-require-extends',
                        'require-implements',
                        'phpstan-require-implements',
                    ],
                    [
                        'consistent-constructor',
                        'phpstan-consistent-constructor',
                        'immutable',
                        'phpstan-immutable',
                        'pure',
                        'phpstan-pure',
                        'impure',
                        'phpstan-impure',
                        'readonly',
                        'phpstan-readonly',
                        'readonly-allow-private-mutation',
                        'phpstan-readonly-allow-private-mutation',
                        'allow-private-mutation',
                        'phpstan-allow-private-mutation',
                    ],
                    [
                        'final',
                        'global',
                        'abstract',
                        'static',
                        'access',
                        'staticvar',
                        'var',
                        'phpstan-var',
                    ],
                    [
                        'uses',
                    ],
                    [
                        'name',
                    ],
                    [
                        'method',
                        'phpstan-method',
                    ],
                    [
                        'property',
                        'phpstan-property',
                        'property-read',
                        'phpstan-property-read',
                        'property-write',
                        'phpstan-property-write',
                    ],
                    [
                        'param',
                        'phpstan-param',
                        'param-out',
                        'phpstan-param-out',
                        'param-immediately-invoked-callable',
                        'phpstan-param-immediately-invoked-callable',
                        'param-later-invoked-callable',
                        'phpstan-param-later-invoked-callable',
                        'param-closure-this',
                        'phpstan-param-closure-this',
                    ],
                    [
                        'return',
                        'phpstan-return',
                    ],
                    [
                        'throws',
                        'phpstan-throws',
                    ],
                    [
                        'assert',
                        'phpstan-assert',
                        'assert-if-true',
                        'phpstan-assert-if-true',
                        'assert-if-false',
                        'phpstan-assert-if-false',
                        'self-out',
                        'phpstan-self-out',
                        'this-out',
                        'phpstan-this-out',
                    ],
                    [
                        'link',
                        'see',
                        'tutorial',
                    ],
                    [
                        'example',
                    ],
                ],
            ],
            'phpdoc_to_comment' => [
                'allow_before_return_statement' => true,
                'ignored_tags'                  => [
                    'disregard',
                ],
            ],
            'phpdoc_types_order' => [
                'null_adjustment' => 'always_last',
                'sort_algorithm'  => 'none',
            ],
            'php_unit_internal_class' => [
                'types' => [
                    'abstract',
                    'final',
                    'normal',
                ],
            ],
            'psr_autoloading' => [
                'dir' => ComposerJson::forProjectUsingThisLibrary()->getFirstAutoloadDirectory(),
            ],
            'regular_callable_call' => true,
            'return_assignment'     => [
                'skip_named_var_tags' => true,
            ],
            'self_static_accessor' => true,
            'simplified_if_return' => true,
            'single_quote'         => [
                'strings_containing_single_quote_chars' => true,
            ],
            'standardize_increment'       => false,
            'static_lambda'               => true,
            'strict_comparison'           => true,
            'strict_param'                => true,
            'trailing_comma_in_multiline' => [
                'elements' => [
                    'arrays',
                    'array_destructuring',
                    'arguments',
                    'match',
                    'parameters',
                ],
            ],
            'unary_operator_spaces' => [
                'only_dec_inc' => false,
            ],
            'yoda_style' => [
                'equal'            => false,
                'identical'        => false,
                'less_and_greater' => false,
            ],
        ];

        if (Package::PhpCsFixerCustomFixers->isInstalled()) {
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

        $config->setRules(array_filter($rules, Str::isNonDecimalIntString(...), ARRAY_FILTER_USE_KEY));

        return new self($config);
    }

    /**
     * Add rules, keeping the ones already configured.
     *
     * Upstream's own `setRules()` replaces the whole set, which silently discards this package's
     * baseline. This one merges, and {@see self::setRules()} is there when replacing is what was
     * meant.
     *
     * @param array<non-empty-string, array<string, mixed>|bool> $rules map of rule name to configuration
     *
     * @example
     * ```php
     * $builder->addRules(['simplified_null_return' => true]);
     * ```
     */
    public function addRules(array $rules): self
    {
        $this->phpCsFixerConfig->setRules(array_merge($this->phpCsFixerConfig->getRules(), $rules));

        return $this;
    }

    /**
     * Set rules, discarding every rule configured so far.
     *
     * @param array<non-empty-string, array<string, mixed>|bool> $rules map of rule name to configuration
     *
     * @example
     * ```php
     * $builder->setRules(['@PSR12' => true]);
     * ```
     */
    public function setRules(array $rules): self
    {
        $this->phpCsFixerConfig->setRules($rules);

        return $this;
    }

    /**
     * Remove rules by name.
     *
     * @param list<non-empty-string> $rules rule names to drop
     *
     * @example
     * ```php
     * $builder->removeRules(['strict_comparison']);
     * ```
     */
    public function removeRules(array $rules): self
    {
        $this->phpCsFixerConfig->setRules(array_diff_key($this->phpCsFixerConfig->getRules(), array_fill_keys($rules, true)));

        return $this;
    }

    /**
     * Finalize the builder into the config php-cs-fixer consumes.
     *
     * @return PhpCsFixerConfig configured Config instance ready for php-cs-fixer
     */
    public function build(): PhpCsFixerConfig
    {
        return $this->phpCsFixerConfig;
    }
}

// @codeCoverageIgnoreStart
return PhpCsFixer::getConfig();
// @codeCoverageIgnoreEnd
