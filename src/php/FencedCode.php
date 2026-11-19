<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use Brnshkr\Config\Exception\UnreachableException;
use Brnshkr\Config\Tests\FencedCodeTest;
use Closure;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode as FencedCodeNode;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Parser\MarkdownParser;
use ParseError;
use RuntimeException;

use function array_filter;
use function array_first;
use function array_map;
use function array_splice;
use function array_values;
use function class_exists;
use function count;
use function file_put_contents;
use function is_dir;
use function is_file;
use function max;
use function mkdir;
use function rmdir;
use function sprintf;
use function token_get_all;
use function uniqid;
use function unlink;

use const TOKEN_PARSE;

/**
 * @internal
 *
 * @see FencedCodeTest
 */
final readonly class FencedCode
{
    public const string LANGUAGE_MAKEFILE = 'makefile';
    public const string LANGUAGE_PHP      = 'php';
    public const string LANGUAGE_TWIG     = 'twig';
    public const string LANGUAGE_XML      = 'xml';

    public const string OPENING_TAG = "<?php\n";

    public const string SCRATCH_DIRECTORY = 'fenced-code';

    /**
     * @param non-empty-string $language
     * @param non-negative-int $firstLineIndex
     * @param non-negative-int $lineCount
     */
    private function __construct(
        public string $code,
        public string $language,
        public int $firstLineIndex,
        private int $lineCount,
        private string $linePrefix,
    ) {}

    /**
     * @return list<self>
     */
    public static function fromMarkdown(string $markdown): array
    {
        return self::findFencedCodes(Str::split($markdown, "\n"), []);
    }

    /**
     * @return list<self>
     */
    public static function fromDocComment(string $docComment): array
    {
        $decorations = [];
        $contents    = [];

        foreach (Str::split($docComment, "\n") as $line) {
            $unindentedLine = Str::trimPrefix(Str::trim($line, " \t", 'start'), '/**');
            $starlessLine   = Str::trimPrefix(Str::trimPrefix($unindentedLine, '*'), ' ');
            $content        = Str::startsWith($unindentedLine, '*/') ? '' : $starlessLine;
            $decorations[]  = Str::slice($line, 0, Str::length($line) - Str::length($content));
            $contents[]     = $content;
        }

        return self::findFencedCodes($contents, $decorations);
    }

    /**
     * @param list<self> $fencedCodes
     *
     * @return list<self>
     */
    public static function filterByLanguage(array $fencedCodes, string $language): array
    {
        return array_values(array_filter(
            $fencedCodes,
            static fn (self $fencedCode): bool => $fencedCode->language === $language,
        ));
    }

    public static function isParsablePhp(string $code): bool
    {
        try {
            return token_get_all(self::OPENING_TAG . $code, TOKEN_PARSE) !== [];
        } catch (ParseError) {
            return false;
        }
    }

    /**
     * @template TResult
     *
     * @param Closure(non-empty-string $scratchDirectory): TResult $callback
     *
     * @return ?TResult
     */
    public static function runInScratchDirectory(
        string $baseDirectory,
        string $fileName,
        string $contents,
        Closure $callback,
    ): mixed {
        $scratchDirectory = sprintf(
            '%s/%s/%s',
            $baseDirectory,
            self::SCRATCH_DIRECTORY,
            uniqid(more_entropy: true),
        );

        $scratchFilePath = $scratchDirectory . '/' . $fileName;

        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/filesystem here to keep package as lightweight as possible)
        if (!mkdir($scratchDirectory, recursive: true) && !is_dir($scratchDirectory)) {
            return null;
        }

        try {
            // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/filesystem here to keep package as lightweight as possible)
            return file_put_contents($scratchFilePath, $contents) === false ? null : $callback($scratchDirectory);
        } finally {
            if (is_file($scratchFilePath)) {
                // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/filesystem here to keep package as lightweight as possible)
                unlink($scratchFilePath);
            }

            // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/filesystem here to keep package as lightweight as possible)
            rmdir($scratchDirectory);
        }
    }

    public function replaceIn(string $text, string $code): string
    {
        $lines = Str::split($text, "\n");

        $prefixedCodeLines = array_map(
            fn (string $codeLine): string => $codeLine === ''
                ? Str::trim($this->linePrefix, " \t", 'end')
                : $this->linePrefix . $codeLine,
            Str::split($code, "\n"),
        );

        array_splice($lines, $this->firstLineIndex, $this->lineCount, $prefixedCodeLines);

        return Str::join($lines, "\n");
    }

    /**
     * @param list<string> $lines
     * @param list<string> $decorations
     *
     * @return list<self>
     */
    private static function findFencedCodes(array $lines, array $decorations): array
    {
        if (!class_exists(MarkdownParser::class)) {
            return [];
        }

        try {
            $environment = new Environment();

            $environment->addExtension(new CommonMarkCoreExtension());

            $document = new MarkdownParser($environment)->parse(Str::join($lines, "\n"));
        } catch (CommonMarkException) {
            return [];
        }

        $skipMarker  = self::getSkipMarker();
        $fencedCodes = [];

        foreach ($document->iterator() as $node) {
            $previousNode = $node->previous();

            $isSkipped = $previousNode instanceof HtmlBlock
                && Str::trim($previousNode->getLiteral()) === $skipMarker;

            if (!$node instanceof FencedCodeNode || $isSkipped) {
                continue;
            }

            $language       = Str::toLowerCase(array_first($node->getInfoWords()) ?? '');
            $firstLineIndex = max(0, $node->getStartLine() ?? 0);
            $code           = Str::trimSuffix($node->getLiteral(), "\n");
            $codeLines      = Str::split($code, "\n");

            if (Str::isEmpty($language)) {
                continue;
            }

            $fencedCodes[] = new self(
                $code,
                $language,
                $firstLineIndex,
                count($codeLines),
                ($decorations[$firstLineIndex - 1] ?? '') . self::getLinePrefix($lines, $firstLineIndex, $codeLines),
            );
        }

        return $fencedCodes;
    }

    /**
     * @param list<string> $lines
     * @param list<string> $codeLines
     */
    private static function getLinePrefix(array $lines, int $firstLineIndex, array $codeLines): string
    {
        foreach ($codeLines as $index => $codeLine) {
            if (!Str::isEmpty($codeLine)) {
                $sourceLine = $lines[$firstLineIndex + $index] ?? '';

                return Str::slice($sourceLine, 0, Str::length($sourceLine) - Str::length($codeLine));
            }
        }

        return '';
    }

    private static function getSkipMarker(): string
    {
        try {
            return sprintf('<!-- %s-skip -->', ComposerJson::forThisLibrary()->getPackageOrganization());
        } catch (RuntimeException $runtimeException) {
            throw UnreachableException::wrap($runtimeException);
        }
    }
}
