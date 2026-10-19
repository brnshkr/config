<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Exception\UnreachableException;
use Brnshkr\Config\FencedCode;
use Brnshkr\Config\FileFinder;
use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\PhpStan\Rule\FencedCodeRuleTest;
use Closure;
use Override;
use PhpParser\Comment\Doc;
use PhpParser\Error;
use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\NodeAbstract;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\VirtualNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use ReflectionException;
use RuntimeException;

use function array_first;
use function array_map;
use function is_string;
use function max;
use function sprintf;

/**
 * Reports code blocks in docblocks and Markdown pages that do not parse, call missing code or pass wrong arguments.
 *
 * No test runs an example, so a renamed method or a dropped parameter leaves it wrong without anyone noticing.
 * Each PHP block is checked like code of its own: names resolve through its own `use` and `namespace` lines,
 * then through the file's, and names under a placeholder namespace are skipped.
 * An XML block must parse and match the schema it names, a Makefile block must parse with `make`
 * and, when it includes the shared Makefile, pass `make help`.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @phpstan-type ExampleError array{
 *     message: non-empty-string,
 *     identifier: non-empty-string,
 *     line: int,
 * }
 * @phpstan-type NestedDoc array{
 *     text: string,
 *     line: int,
 *     nameContext: NameContext,
 *     documentedMethod: ?DocumentedMethod,
 * }
 * @phpstan-import-type DocumentedMethod from PhpExampleAnalyzer
 *
 * @implements Rule<NodeAbstract>
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/FencedCodeRule.md
 * @see FencedCodeRuleTest
 *
 * @example
 * ```php
 * use Brnshkr\Config\PhpStan;
 *
 * PhpStan::getBuilder()
 *     ->replaceRule(FencedCodeRule::class, [
 *         'placeholderNamespaces' => ['Acme', 'Vendor'],
 *     ])
 * ;
 * ```
 */
final readonly class FencedCodeRule implements Rule
{
    use RuleTrait;

    /**
     * @internal
     */
    public const string PROBLEM_PARSE = 'Code block does not parse';

    private const string IDENTIFIER_PARSE = 'phpstan.parse';

    private const string SHARED_MAKEFILE_PATH = '/conf/Makefile';

    private XmlExampleAnalyzer $xmlExampleAnalyzer;

    private MakefileExampleAnalyzer $makefileExampleAnalyzer;

    /**
     * @internal invoked by PHPStan
     *
     * @param ReflectionProvider $reflectionProvider - PHPStan reflection provider (auto-wired)
     * @param FileTypeMapper $fileTypeMapper - PHPStan docblock resolver, applies the file's namespace and imports (auto-wired)
     * @param Container $container - PHPStan container, read for its temporary directory (auto-wired)
     * @param list<string> $placeholderNamespaces - namespaces of the placeholder classes examples may use
     *
     * @throws RuntimeException when a `composer.json` cannot be read
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private FileTypeMapper $fileTypeMapper,
        Container $container,
        private array $placeholderNamespaces = ['Acme'],
    ) {
        $temporaryDirectory  = $container->getParameters()['tmpDir'] ?? null;
        $libraryComposerJson = ComposerJson::forThisLibrary();

        if (!is_string($temporaryDirectory) || Str::isEmpty($temporaryDirectory)) {
            throw new UnreachableException('PHPStan always sets its `tmpDir` parameter.');
        }

        $this->xmlExampleAnalyzer = new XmlExampleAnalyzer(ComposerJson::forProjectUsingThisLibrary()->getDirectory());

        $sharedMakefileSuffix = Str::join([
            $libraryComposerJson->getPackageOrganization(),
            $libraryComposerJson->getPackageName(),
        ], '/') . self::SHARED_MAKEFILE_PATH;

        $this->makefileExampleAnalyzer = new MakefileExampleAnalyzer(
            $temporaryDirectory,
            $libraryComposerJson->getDirectory() . self::SHARED_MAKEFILE_PATH,
            [
                '/' . $sharedMakefileSuffix,
                '/@' . $sharedMakefileSuffix,
            ],
        );
    }

    /**
     * @internal invoked by PHPStan
     */
    #[Override]
    public function getNodeType(): string
    {
        return NodeAbstract::class;
    }

    /**
     * @internal invoked by PHPStan
     *
     * @return list<IdentifierRuleError>
     *
     * @throws ReflectionException
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $doc = $node->getDocComment();

        if ($node instanceof InlineHTML && FileFinder::isMarkdown($scope->getFile())) {
            $fencedCodes = FencedCode::fromMarkdown($node->value);
            $firstLine   = $node->getStartLine();
        } elseif ($node instanceof Stmt && !$node instanceof VirtualNode && $doc instanceof Doc) {
            $fencedCodes = FencedCode::fromDocComment($doc->getText());
            $firstLine   = $doc->getStartLine();
        } else {
            return [];
        }

        $className = $scope->getClassReflection()?->getName()
            ?? ($node instanceof ClassLike ? $node->namespacedName?->toString() : null);

        $resolveFileClassName = $this->getFileClassNameResolver($node, $scope, $className);
        $errors               = [];

        $documentedMethod = $node instanceof ClassMethod && $className !== null
            ? [
                'className'  => $className,
                'methodName' => $node->name->toString(),
            ]
            : null;

        foreach (FencedCode::filterByLanguage($fencedCodes, FencedCode::LANGUAGE_PHP) as $fencedCode) {
            $errors = [
                ...$errors,
                ...$this->processCode(
                    $fencedCode->code,
                    $firstLine + $fencedCode->firstLineIndex,
                    $resolveFileClassName,
                    $documentedMethod,
                ),
            ];
        }

        return [
            ...$errors,
            ...self::analyzeFencedCodes(
                FencedCode::filterByLanguage($fencedCodes, FencedCode::LANGUAGE_XML),
                $firstLine,
                $this->xmlExampleAnalyzer->analyze(...),
            ),
            ...self::analyzeFencedCodes(
                FencedCode::filterByLanguage($fencedCodes, FencedCode::LANGUAGE_MAKEFILE),
                $firstLine,
                $this->makefileExampleAnalyzer->analyze(...),
            ),
        ];
    }

    /**
     * @internal
     *
     * @return ExampleError
     */
    public static function buildExampleError(string $problem, string $detail, int $line): array
    {
        return [
            'message'    => $problem . ': ' . Str::trimSuffix(Str::trim($detail), '.') . '.',
            'identifier' => self::getIdentifier(),
            'line'       => $line,
        ];
    }

    /**
     * @param list<FencedCode> $fencedCodes
     * @param Closure(string $code): list<ExampleError> $analyze
     *
     * @return list<IdentifierRuleError>
     */
    private static function analyzeFencedCodes(array $fencedCodes, int $firstLine, Closure $analyze): array
    {
        $errors = [];

        foreach ($fencedCodes as $fencedCode) {
            foreach ($analyze($fencedCode->code) as $exampleError) {
                $errors[] = self::buildBlockError([
                    ...$exampleError,
                    'line' => $firstLine + $fencedCode->firstLineIndex + $exampleError['line'] - 1,
                ]);
            }
        }

        return $errors;
    }

    /**
     * @return Closure(Name $name): string
     */
    private function getFileClassNameResolver(Node $node, Scope $scope, ?string $className): Closure
    {
        $functionName = match (true) {
            $node instanceof Function_   => $node->namespacedName?->toString(),
            $node instanceof ClassMethod => $node->name->toString(),
            default                      => $scope->getFunctionName(),
        };

        return fn (Name $name): string => array_first(
            $this->fileTypeMapper->getResolvedPhpDoc(
                fileName: $scope->getFile(),
                className: $className,
                traitName: null,
                functionName: $functionName,
                docComment: '/** @return ' . $name->toString() . ' */',
            )->getReturnTag()?->getType()->getObjectClassNames() ?? [],
        ) ?? $name->toString();
    }

    /**
     * @param Closure(Name $name): string $resolveOuterClassName
     * @param ?DocumentedMethod $documentedMethod
     *
     * @return list<IdentifierRuleError>
     *
     * @throws ReflectionException
     */
    private function processCode(
        string $code,
        int $firstLine,
        Closure $resolveOuterClassName,
        ?array $documentedMethod,
    ): array {
        try {
            $statements = new ParserFactory()
                ->createForNewestSupportedVersion()
                ->parse(FencedCode::OPENING_TAG . $code) ?? []
            ;
        } catch (Error $error) {
            return [self::buildBlockError([
                'message' => sprintf(
                    '%s: %s.',
                    self::PROBLEM_PARSE,
                    $error->getRawMessage(),
                ),
                'identifier' => self::IDENTIFIER_PARSE,
                'line'       => $firstLine + max(0, $error->getStartLine() - 2),
            ])];
        }

        $nestedDocs = self::collectNestedDocs($statements);

        $resolveClassName = static fn (Name $name): string => self::preferResolvedName(
            $name->getAttribute('resolvedName'),
            $name,
            $resolveOuterClassName,
        );

        $phpExampleAnalyzer = new PhpExampleAnalyzer(
            $this->reflectionProvider,
            $resolveClassName,
            $this->placeholderNamespaces,
            $documentedMethod,
        );

        $errors = array_map(
            static fn (array $exampleError): IdentifierRuleError => self::buildBlockError([
                ...$exampleError,
                'line' => $firstLine + $exampleError['line'] - 2,
            ]),
            $phpExampleAnalyzer->analyze($statements),
        );

        foreach ($nestedDocs as $nestedDoc) {
            $resolveNestedOuterClassName = static fn (Name $name): string => self::preferResolvedName(
                $nestedDoc['nameContext']->getResolvedClassName($name),
                $name,
                $resolveClassName,
            );

            $nestedFencedCodes = FencedCode::filterByLanguage(
                FencedCode::fromDocComment($nestedDoc['text']),
                FencedCode::LANGUAGE_PHP,
            );

            foreach ($nestedFencedCodes as $nestedFencedCode) {
                $errors = [
                    ...$errors,
                    ...$this->processCode(
                        $nestedFencedCode->code,
                        $firstLine + $nestedDoc['line'] - 2 + $nestedFencedCode->firstLineIndex,
                        $resolveNestedOuterClassName,
                        $nestedDoc['documentedMethod'],
                    ),
                ];
            }
        }

        return $errors;
    }

    /**
     * @param ExampleError $exampleError
     */
    private static function buildBlockError(array $exampleError): IdentifierRuleError
    {
        return RuleErrorBuilder::message($exampleError['message'])
            ->identifier($exampleError['identifier'])
            ->line($exampleError['line'])
            ->build()
        ;
    }

    /**
     * @param Closure(Name $name): string $resolveOuterClassName
     */
    private static function preferResolvedName(mixed $resolvedName, Name $name, Closure $resolveOuterClassName): string
    {
        return $resolvedName instanceof Name
            && ($name->isFullyQualified() || $resolvedName->toString() !== $name->toString())
            ? $resolvedName->toString()
            : $resolveOuterClassName($name);
    }

    /**
     * @param array<array-key, Stmt> $statements
     *
     * @return list<NestedDoc>
     */
    private static function collectNestedDocs(array $statements): array
    {
        $nameResolver = new NameResolver(null, ['replaceNodes' => false]);

        $docCollector = new
        /**
         * @phpstan-import-type NestedDoc from FencedCodeRule
         */
        class($nameResolver) extends NodeVisitorAbstract {
            /**
             * @var list<NestedDoc>
             */
            public array $nestedDocs = [];

            private ?string $className = null;

            public function __construct(
                private readonly NameResolver $nameResolver,
            ) {}

            #[Override]
            public function enterNode(Node $node): null
            {
                if ($node instanceof ClassLike && $node->namespacedName instanceof Name) {
                    $this->className = $node->namespacedName->toString();
                }

                $doc = $node->getDocComment();

                if (!$node instanceof Stmt || !$doc instanceof Doc) {
                    return null;
                }

                $this->nestedDocs[] = [
                    'text'             => $doc->getText(),
                    'line'             => $doc->getStartLine(),
                    'nameContext'      => clone $this->nameResolver->getNameContext(),
                    'documentedMethod' => $node instanceof ClassMethod && $this->className !== null
                        ? [
                            'className'  => $this->className,
                            'methodName' => $node->name->toString(),
                        ]
                        : null,
                ];

                return null;
            }

            #[Override]
            public function leaveNode(Node $node): null
            {
                if ($node instanceof ClassLike) {
                    $this->className = null;
                }

                return null;
            }
        };

        new NodeTraverser($nameResolver, $docCollector)->traverse($statements);

        return $docCollector->nestedDocs;
    }
}
