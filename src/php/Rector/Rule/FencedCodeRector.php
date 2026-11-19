<?php

declare(strict_types=1);

namespace Brnshkr\Config\Rector\Rule;

use Brnshkr\Config\FencedCode;
use Brnshkr\Config\FileFinder;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Rector\Rule\FencedCodeRectorTest;
use InvalidArgumentException;
use Override;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Token;
use PHPStan\AnalysedCodeException;
use Rector\Application\Provider\CurrentFileProvider;
use Rector\ChangesReporting\ValueObject\RectorWithLineChange;
use Rector\CodingStyle\ClassNameImport\UsedImportsResolver;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\SimpleParameterProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Contract\Rector\RectorInterface;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\NodeTypeResolver\NodeScopeAndMetadataDecorator;
use Rector\PhpParser\Node\FileNode;
use Rector\PhpParser\NodeTraverser\RectorNodeTraverser;
use Rector\PhpParser\Parser\RectorParser;
use Rector\PhpParser\Printer\BetterStandardPrinter;
use Rector\PHPUnit\CodeQuality\Rector\Class_\AddSeeTestAnnotationRector;
use Rector\Rector\AbstractRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use Rector\ValueObject\Application\File;

use function array_is_list;
use function array_key_exists;
use function array_reverse;
use function array_values;
use function in_array;
use function is_a;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Refactors PHP code blocks in docblocks and Markdown pages with the configured rules.
 *
 * Rector refactors code, not the examples written about it, so they drift from the code they document.
 * This rule runs each block as a file of its own, minus rules that only fit whole files or would change what an
 * example shows, e.g. `DeclareStrictTypesRector` and `RemoveEmptyClassMethodRector`.
 * A block Rector cannot analyze is left alone.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/rector/rules/FencedCodeRector.md
 * @see FencedCodeRectorTest
 *
 * @example
 * ```php
 * use Acme\Rector\SomeRector;
 * use Brnshkr\Config\Rector;
 * use Brnshkr\Config\Rector\Rule\FencedCodeRector;
 *
 * return Rector::getBuilder()
 *     ->build()
 *     ->withConfiguredRule(FencedCodeRector::class, [
 *         FencedCodeRector::SKIPS => [
 *             SomeRector::class,
 *         ],
 *     ])
 * ;
 * ```
 */
// @phpstan-ignore brnshkr.interfaceSuffix (Rector names every rule *Rector)
final class FencedCodeRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * Lists further rules kept out of code blocks.
     *
     * Numeric, so every config naming it adds to the list instead of replacing it.
     */
    public const int SKIPS = 0;

    /**
     * @disregard P1009 Some skipped classes come bundled with rector and are not picked up by intelephense
     */
    private const array ALWAYS_SKIPPED_RULES = [
        AddSeeTestAnnotationRector::class,
        DeclareStrictTypesRector::class,
        RemoveEmptyClassMethodRector::class,
        RemoveUnusedPromotedPropertyRector::class,
        RemoveUnusedPublicMethodParameterRector::class,
        SafeDeclareStrictTypesRector::class,
    ];

    private const string FENCE_FILE_NAME = 'fence.php';

    // @phpstan-ignore brnshkr.internalUsage (Rector offers no public way to run its rules on a code block)
    private ?RectorNodeTraverser $rectorNodeTraverser = null;

    /**
     * @internal invoked by Rector
     */
    public function __construct(
        private readonly RectorParser $rectorParser,
        private readonly NodeScopeAndMetadataDecorator $nodeScopeAndMetadataDecorator,
        private readonly BetterStandardPrinter $betterStandardPrinter,
        // @phpstan-ignore brnshkr.internalUsage (Rector offers no public way to run its rules on a code block)
        private readonly CurrentFileProvider $currentFileProvider,
        private readonly UsedImportsResolver $usedImportsResolver,
    ) {}

    /**
     * @internal Brnshkr\Config\Rector
     */
    // @phpstan-ignore brnshkr.internalUsage (Rector offers no public way to run its rules on a code block)
    public function setRectorNodeTraverser(RectorNodeTraverser $rectorNodeTraverser): void
    {
        $this->rectorNodeTraverser = $rectorNodeTraverser;

        self::skipInFences(self::ALWAYS_SKIPPED_RULES);
    }

    /**
     * @internal invoked by Rector
     *
     * @param array<array-key, mixed> $configuration - lists of further rules kept out of code blocks
     *
     * @throws InvalidArgumentException when an entry is not a list of rule classes
     */
    #[Override]
    public function configure(array $configuration): void
    {
        $skippedRules = [];

        $message = sprintf(
            '`%s::SKIPS` takes a list of rule classes.',
            self::class,
        );

        foreach ($configuration as $rules) {
            if (!is_array($rules) || !array_is_list($rules)) {
                throw new InvalidArgumentException($message);
            }

            foreach ($rules as $rule) {
                if (!is_string($rule) || !is_a($rule, RectorInterface::class, true)) {
                    throw new InvalidArgumentException($message);
                }

                $skippedRules[] = $rule;
            }
        }

        self::skipInFences($skippedRules);
    }

    /**
     * @internal invoked by Rector
     *
     * @return list<class-string<Node>>
     */
    #[Override]
    public function getNodeTypes(): array
    {
        return [Stmt::class];
    }

    /**
     * @internal invoked by Rector
     */
    #[Override]
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof InlineHTML) {
            return FileFinder::isMarkdown($this->getFile()->getFilePath()) ? $this->refactorMarkdown($node) : null;
        }

        $doc = $node->getDocComment();

        if (!$doc instanceof Doc) {
            return null;
        }

        $refactoredText = $this->refactorFencedCodes(
            $doc->getText(),
            FencedCode::fromDocComment($doc->getText()),
            $doc->getStartLine(),
        );

        if ($refactoredText === $doc->getText()) {
            return null;
        }

        $node->setDocComment(new Doc($refactoredText));

        return $node;
    }

    /**
     * @param list<class-string<RectorInterface>> $rules
     */
    private static function skipInFences(array $rules): void
    {
        // @phpstan-ignore brnshkr.internalUsage (Rector offers no public way to add a path to an existing skip)
        $skips = SimpleParameterProvider::provideArrayParameter(Option::SKIP);

        foreach ($rules as $rule) {
            $isSkippedEverywhere = in_array($rule, $skips, true)
                || (array_key_exists($rule, $skips) && $skips[$rule] === null);

            if ($isSkippedEverywhere) {
                continue;
            }

            $existingSkip = $skips[$rule] ?? [];

            $skips[$rule] = [
                ...is_array($existingSkip) ? array_values($existingSkip) : [$existingSkip],
                self::getCacheDirectory() . '/' . FencedCode::SCRATCH_DIRECTORY . '/*',
            ];
        }

        // @phpstan-ignore brnshkr.internalUsage (Rector offers no public way to add a path to an existing skip)
        SimpleParameterProvider::setParameter(Option::SKIP, $skips);
    }

    private static function getCacheDirectory(): string
    {
        // @phpstan-ignore brnshkr.internalUsage (Rector offers no public way to read its cache directory)
        return SimpleParameterProvider::provideStringParameter(Option::CACHE_DIR);
    }

    private function refactorMarkdown(InlineHTML $inlineHtml): ?InlineHTML
    {
        $refactoredValue = $this->refactorFencedCodes(
            $inlineHtml->value,
            FencedCode::fromMarkdown($inlineHtml->value),
            $inlineHtml->getStartLine(),
        );

        if ($refactoredValue === $inlineHtml->value) {
            return null;
        }

        $originalInlineHtml = $inlineHtml->getAttribute(AttributeKey::ORIGINAL_NODE);
        $pageToken          = $this->getFile()->getOldTokens()[$inlineHtml->getStartTokenPos()] ?? null;

        if (!$pageToken instanceof Token) {
            return null;
        }

        // NOTICE: The printer wraps a changed page in PHP tags, so the page's own token carries the change
        $pageToken->text   = $refactoredValue;
        $inlineHtml->value = $refactoredValue;

        if ($originalInlineHtml instanceof InlineHTML) {
            $originalInlineHtml->value = $refactoredValue;
        }

        return $inlineHtml;
    }

    /**
     * @param list<FencedCode> $fencedCodes
     */
    private function refactorFencedCodes(string $text, array $fencedCodes, int $firstLine): string
    {
        foreach (array_reverse(FencedCode::filterByLanguage($fencedCodes, FencedCode::LANGUAGE_PHP)) as $fencedCode) {
            $refactoredCode = $this->refactorCode($fencedCode->code, $firstLine + $fencedCode->firstLineIndex);

            if ($refactoredCode !== null && $refactoredCode !== $fencedCode->code) {
                $text = $fencedCode->replaceIn($text, $refactoredCode);
            }
        }

        return $text;
    }

    private function refactorCode(string $code, int $codeLine): ?string
    {
        $rectorNodeTraverser = $this->rectorNodeTraverser;

        // @phpstan-ignore brnshkr.internalUsage (Rector offers no public way to run its rules on a code block)
        if (!$rectorNodeTraverser instanceof RectorNodeTraverser || !FencedCode::isParsablePhp($code)) {
            return null;
        }

        $fenceCode = FencedCode::OPENING_TAG . $code;

        return FencedCode::runInScratchDirectory(
            self::getCacheDirectory(),
            self::FENCE_FILE_NAME,
            $fenceCode,
            function (string $scratchDirectory) use ($fenceCode, $codeLine, $rectorNodeTraverser): ?string {
                // @phpstan-ignore brnshkr.internalUsage (Rector offers no public way to run its rules on a code block)
                $outerFile = $this->currentFileProvider->getFile();
                $fenceFile = new File($scratchDirectory . '/' . self::FENCE_FILE_NAME, $fenceCode);

                try {
                    $refactoredCode = $this->refactorFenceFile($fenceFile, $rectorNodeTraverser);
                } catch (AnalysedCodeException) {
                    return null;
                } finally {
                    if ($outerFile instanceof File) {
                        // @phpstan-ignore brnshkr.internalUsage (Rector offers no public way to run its rules on a code block)
                        $this->currentFileProvider->setFile($outerFile);
                    }
                }

                foreach ($fenceFile->getRectorWithLineChanges() as $rectorWithLineChange) {
                    $this->getFile()->addRectorClassWithLine(new RectorWithLineChange(
                        $rectorWithLineChange->getRectorClass(),
                        $codeLine + $rectorWithLineChange->getLine() - 2,
                    ));
                }

                return $refactoredCode;
            },
        );
    }

    // @phpstan-ignore brnshkr.internalUsage (Rector offers no public way to run its rules on a code block)
    private function refactorFenceFile(File $fenceFile, RectorNodeTraverser $rectorNodeTraverser): string
    {
        // @phpstan-ignore brnshkr.internalUsage (Rector offers no public way to run its rules on a code block)
        $this->currentFileProvider->setFile($fenceFile);

        $stmtsAndTokens = $this->rectorParser->parseFileContentToStmtsAndTokens($fenceFile->getOriginalFileContent());
        $statements     = $stmtsAndTokens->getStmts();

        new NodeTraverser(new NameResolver(null, [
            'preserveOriginalNames' => true,
            'replaceNodes'          => false,
        ]))->traverse($statements);

        $fileNodes = [new FileNode($statements, $this->usedImportsResolver->resolveForStmts($statements))];

        $fenceFile->hydrateStmtsAndTokens(
            $this->nodeScopeAndMetadataDecorator->decorateNodesFromFile($fenceFile->getFilePath(), $fileNodes),
            $fileNodes,
            $stmtsAndTokens->getTokens(),
        );

        do {
            $fenceFile->changeHasChanged(false);

            // @phpstan-ignore brnshkr.internalUsage (Rector offers no public way to run its rules on a code block)
            $fenceFile->changeNewStmts($rectorNodeTraverser->traverse($fenceFile->getNewStmts()));

            $fenceFile->changeFileContent($this->betterStandardPrinter->printFormatPreserving(
                $fenceFile->getNewStmts(),
                $fenceFile->getOldStmts(),
                $fenceFile->getOldTokens(),
            ));
        } while ($fenceFile->hasChanged());

        return Str::trim(Str::trimPrefix($fenceFile->getFileContent(), FencedCode::OPENING_TAG), "\n");
    }
}
