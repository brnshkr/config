<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpCsFixer\Fixer;

use Brnshkr\Config\Exception\UnreachableException;
use Brnshkr\Config\FencedCode;
use Brnshkr\Config\FileFinder;
use Brnshkr\Config\Package;
use Brnshkr\Config\PhpCsFixer\Fixer\Trait\FixerTrait;
use Brnshkr\Config\ProjectDirectory;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\PhpCsFixer\Fixer\FencedCodeFixerTest;
use Brnshkr\Config\TwigCsFixer;
use Override;
use PhpCsFixer\ConfigInterface;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet\RuleSet;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\SplFileInfo as FinderSplFileInfo;
use TwigCsFixer\Config\Config as TwigCsFixerConfig;
use TwigCsFixer\Environment\StubbedEnvironment;
use TwigCsFixer\Exception\CannotFixFileException;
use TwigCsFixer\Exception\CannotTokenizeException;
use TwigCsFixer\Ruleset\Ruleset as TwigRuleset;
use TwigCsFixer\Runner\Fixer as TwigFixer;
use TwigCsFixer\Token\Tokenizer;

use function array_diff_key;
use function array_fill_keys;
use function array_filter;
use function array_reverse;
use function array_values;
use function is_array;
use function is_readable;
use function is_string;

use const T_DOC_COMMENT;
use const T_INLINE_HTML;

/**
 * Formats PHP and Twig code blocks in docblocks and Markdown pages with the configured rules.
 *
 * PHP-CS-Fixer formats code, not the examples written about it, so their style drifts from the code they document.
 * This fixer formats each block as a file of its own, minus rules that only fit whole files or would rewrite
 * an example's names, e.g. `declare_strict_types` and `fully_qualified_strict_types`. Twig blocks take the project's
 * twig-cs-fixer rules while twig-cs-fixer is installed.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @phpstan-type Configuration array{
 *     excluded_rules?: list<string>,
 * }
 * @phpstan-type ResolvedConfiguration array{
 *     excluded_rules: list<string>,
 * }
 *
 * @implements ConfigurableFixerInterface<Configuration, ResolvedConfiguration>
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/php-cs-fixer/rules/FencedCodeFixer.md
 * @see FencedCodeFixerTest
 *
 * @example
 * ```php
 * use Brnshkr\Config\PhpCsFixer;
 * use Brnshkr\Config\PhpCsFixer\Fixer\FencedCodeFixer;
 *
 * PhpCsFixer::getBuilder()
 *     ->addRules([
 *         FencedCodeFixer::name() => [
 *             'excluded_rules' => [
 *                 'yoda_style',
 *             ],
 *         ],
 *     ])
 *     ->build()
 * ;
 * ```
 */
// @phpstan-ignore brnshkr.interfaceSuffix (php-cs-fixer names every fixer *Fixer)
final class FencedCodeFixer implements ConfigurableFixerInterface
{
    use FixerTrait;

    private const string OPTION_EXCLUDED_RULES = 'excluded_rules';

    private const array ALWAYS_EXCLUDED_RULES = [
        'blank_line_after_opening_tag',
        'blank_lines_before_namespace',
        'declare_strict_types',
        'fully_qualified_strict_types',
        'global_namespace_import',
        'header_comment',
        'native_constant_invocation',
        'native_function_invocation',
        'ordered_class_elements',
        'php_unit_internal_class',
        'php_unit_test_class_requires_covers',
    ];

    private const string FENCE_FILE_NAME = 'fence.php';

    private const string TWIG_CS_FIXER_CONFIG_VARIABLE = 'BRNSHKR_TWIG_CS_FIXER_CONFIG';

    /**
     * @var list<string>
     */
    private array $excludedRules = [];

    /**
     * @var ?list<FixerInterface>
     */
    private ?array $fenceFixers = null;

    private ?TwigRuleset $twigRuleset = null;

    private ?bool $isTwigCsFixerInstalled = null;

    /**
     * @internal invoked by PhpCsFixer
     *
     * @param ConfigInterface $phpCsFixerConfig - the config whose rules apply inside code blocks
     */
    public function __construct(
        private readonly ConfigInterface $phpCsFixerConfig,
    ) {}

    /**
     * @internal invoked by php-cs-fixer
     */
    #[Override]
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'PHP and Twig code blocks in docblocks and Markdown pages are formatted with the configured rules.',
            [
                new CodeSample(<<<'PHP'
                    <?php

                    /**
                     * ```php
                     * $user=new User( 'acme' );
                     * ```
                     */
                    function acme(): void {}

                    PHP),
            ],
        );
    }

    /**
     * @internal invoked by php-cs-fixer
     */
    #[Override]
    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            new FixerOptionBuilder(self::OPTION_EXCLUDED_RULES, 'further rules kept out of code blocks')
                ->setAllowedTypes(['string[]'])
                ->setDefault([])
                ->getOption(),
        ]);
    }

    /**
     * @internal invoked by php-cs-fixer
     *
     * @param Configuration $configuration - the `excluded_rules` option
     */
    #[Override]
    public function configure(array $configuration): void
    {
        $resolvedConfiguration = $this->getConfigurationDefinition()->resolve($configuration);
        $excludedRules         = $resolvedConfiguration[self::OPTION_EXCLUDED_RULES] ?? [];

        $this->excludedRules = is_array($excludedRules)
            ? array_values(array_filter($excludedRules, is_string(...)))
            : [];

        $this->fenceFixers = null;
    }

    /**
     * @internal invoked by php-cs-fixer
     */
    #[Override]
    public function getPriority(): int
    {
        return -100;
    }

    /**
     * @internal invoked by php-cs-fixer
     */
    #[Override]
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_DOC_COMMENT, T_INLINE_HTML]);
    }

    /**
     * @internal invoked by php-cs-fixer
     */
    #[Override]
    public function isRisky(): bool
    {
        return false;
    }

    /**
     * @internal invoked by php-cs-fixer
     */
    #[Override]
    // @phpstan-ignore symplify.preferredClass (Required by php-cs-fixer's FixerInterface)
    public function supports(SplFileInfo $file): bool
    {
        return true;
    }

    /**
     * @internal invoked by php-cs-fixer
     */
    #[Override]
    // @phpstan-ignore symplify.preferredClass (Required by php-cs-fixer's FixerInterface)
    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        $isMarkdown = FileFinder::isMarkdown($file->getFilename());

        foreach ($tokens->toArray() as $tokenIndex => $token) {
            $isDocComment = $token->isGivenKind(T_DOC_COMMENT);

            if (!$isDocComment && (!$isMarkdown || !$token->isGivenKind(T_INLINE_HTML))) {
                continue;
            }

            $tokenContent     = $token->getContent();
            $tokenKind        = $isDocComment ? T_DOC_COMMENT : T_INLINE_HTML;
            $formattedContent = $this->formatFencedCodes($tokenContent, $tokenKind);

            if ($formattedContent !== $tokenContent) {
                $tokens[$tokenIndex] = new Token([$tokenKind, $formattedContent]);
            }
        }
    }

    private function formatFencedCodes(string $content, int $tokenKind): string
    {
        $formatters = [
            FencedCode::LANGUAGE_PHP  => $this->formatPhpCode(...),
            FencedCode::LANGUAGE_TWIG => $this->formatTwigCode(...),
        ];

        $fencedCodes = $tokenKind === T_DOC_COMMENT
            ? FencedCode::fromDocComment($content)
            : FencedCode::fromMarkdown($content);

        foreach (array_reverse($fencedCodes) as $fencedCode) {
            $formatter     = $formatters[$fencedCode->language] ?? null;
            $formattedCode = $formatter === null ? null : $formatter($fencedCode->code);

            if ($formattedCode !== null && $formattedCode !== $fencedCode->code) {
                $content = $fencedCode->replaceIn($content, $formattedCode);
            }
        }

        return $content;
    }

    private function formatPhpCode(string $code): ?string
    {
        if (!FencedCode::isParsablePhp($code)) {
            return null;
        }

        $tokens    = Tokens::fromCode(FencedCode::OPENING_TAG . $code);
        $fenceFile = new FinderSplFileInfo(self::FENCE_FILE_NAME, '', self::FENCE_FILE_NAME);

        foreach ($this->getFenceFixers() as $fixer) {
            if ($fixer->isCandidate($tokens) && $fixer->supports($fenceFile)) {
                $fixer->fix($fenceFile, $tokens);
                $tokens->clearEmptyTokens();
            }
        }

        return Str::trim(Str::trimPrefix($tokens->generateCode(), FencedCode::OPENING_TAG), "\n", 'end');
    }

    private function formatTwigCode(string $code): ?string
    {
        if (!$this->isTwigCsFixerInstalled()) {
            return null;
        }

        $this->twigRuleset ??= self::getTwigRuleset();

        try {
            $formattedCode = new TwigFixer(new Tokenizer(new StubbedEnvironment()))->fixFile($code, $this->twigRuleset);
        } catch (CannotFixFileException|CannotTokenizeException) {
            return null;
        }

        return Str::trim($formattedCode, "\n", 'end');
    }

    private function isTwigCsFixerInstalled(): bool
    {
        try {
            return $this->isTwigCsFixerInstalled ??= Package::TwigCsFixer->isInstalled();
        } catch (RuntimeException $runtimeException) {
            throw UnreachableException::wrap($runtimeException);
        }
    }

    private static function getTwigRuleset(): TwigRuleset
    {
        $configPaths = [
            Str::fromEnvironment(self::TWIG_CS_FIXER_CONFIG_VARIABLE),
            ProjectDirectory::getConfig() . '/twig-cs-fixer.php',
            ProjectDirectory::getConfig() . '/twig-cs-fixer.dist.php',
        ];

        foreach ($configPaths as $configPath) {
            $twigCsFixerConfig = !Str::isEmpty($configPath) && is_readable($configPath) ? include $configPath : null;

            if ($twigCsFixerConfig instanceof TwigCsFixerConfig) {
                return $twigCsFixerConfig->getRuleset();
            }
        }

        try {
            return TwigCsFixer::getConfig()->getRuleset();
        } catch (DirectoryNotFoundException|RuntimeException $exception) {
            throw UnreachableException::wrap($exception);
        }
    }

    /**
     * @return list<FixerInterface>
     */
    private function getFenceFixers(): array
    {
        if ($this->fenceFixers !== null) {
            return $this->fenceFixers;
        }

        $rules = array_diff_key(
            // @phpstan-ignore brnshkr.internalUsage (php-cs-fixer offers no public way to resolve rule names to fixers)
            new RuleSet($this->phpCsFixerConfig->getRules())->getRules(),
            array_fill_keys([...self::ALWAYS_EXCLUDED_RULES, ...$this->excludedRules, $this->getName()], true),
        );

        // @phpstan-ignore brnshkr.internalUsage (php-cs-fixer offers no public way to resolve rule names to fixers)
        $fixers = new FixerFactory()
            ->registerBuiltInFixers()
            ->registerCustomFixers($this->phpCsFixerConfig->getCustomFixers())
            ->setWhitespacesConfig(new WhitespacesFixerConfig(
                $this->phpCsFixerConfig->getIndent(),
                $this->phpCsFixerConfig->getLineEnding(),
            ))
            ->useRuleSet(new RuleSet($rules))
            ->getFixers()
        ;

        $this->fenceFixers = [
            ...array_values(array_filter(
                $fixers,
                fn (FixerInterface $fixer): bool => $this->phpCsFixerConfig->getRiskyAllowed() || !$fixer->isRisky(),
            )),
            $this,
        ];

        return $this->fenceFixers;
    }
}
