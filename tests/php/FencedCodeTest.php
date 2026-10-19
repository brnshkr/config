<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\FencedCode;
use Brnshkr\Config\Json;
use Brnshkr\Config\Str;
use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function array_map;
use function sprintf;

/**
 * @internal
 */
#[CoversClass(FencedCode::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(Json::class)]
#[UsesClass(Str::class)]
final class FencedCodeTest extends TestCase
{
    /**
     * @param list<array{string, int}> $expectedFences
     */
    #[DataProvider('provideMarkdownYieldsEveryPhpFenceCommonMarkSeesCases')]
    public function testMarkdownYieldsEveryPhpFenceCommonMarkSees(string $markdown, array $expectedFences): void
    {
        self::assertSame($expectedFences, self::getPhpCodesWithLineIndexes(FencedCode::fromMarkdown($markdown)));
    }

    /**
     * @return iterable<string, array{string, list<array{string, int}>}>
     */
    public static function provideMarkdownYieldsEveryPhpFenceCommonMarkSeesCases(): iterable
    {
        $skipMarker = sprintf('<!-- %s-skip -->', ComposerJson::forThisLibrary()->getPackageOrganization());

        yield 'a backtick fence' => [
            <<<'MD'
                ```php
                return 1;
                ```
                MD,
            [
                ['return 1;', 1],
            ],
        ];

        yield 'a tilde fence' => [
            <<<'MD'
                ~~~php
                return 1;
                ~~~
                MD,
            [
                ['return 1;', 1],
            ],
        ];

        yield 'an uppercase language with attributes' => [
            <<<'MD'
                ```PHP title="Config"
                return 1;
                ```
                MD,
            [
                ['return 1;', 1],
            ],
        ];

        yield 'other languages' => [
            <<<'MD'
                ```phpt
                return 1;
                ```

                ```html+php
                return 2;
                ```
                MD,
            [],
        ];

        yield 'an opening fence indented by three spaces' => [
            <<<'MD'
                   ```php
                     return 1;
                  return 2;
                   ```
                MD,
            [
                [
                    <<<'PHP'
                          return 1;
                        return 2;
                        PHP,
                    1,
                ],
            ],
        ];

        yield 'four spaces of indentation' => [
            <<<'MD'
                    ```php
                    return 1;
                    ```
                MD,
            [],
        ];

        yield 'a fence on a bullet line' => [
            <<<'MD'
                - ```php
                  return 1;
                  ```

                * ```php
                  return 2;
                  ```

                + ```php
                  return 3;
                  ```
                MD,
            [
                ['return 1;', 1],
                ['return 2;', 5],
                ['return 3;', 9],
            ],
        ];

        yield 'a fence on an ordered line' => [
            <<<'MD'
                1. ```php
                   return 1;
                   ```

                1) ```php
                   return 2;
                   ```
                MD,
            [
                ['return 1;', 1],
                ['return 2;', 5],
            ],
        ];

        yield 'a fence at the content width of an item' => [
            <<<'MD'
                - An item:

                  ```php
                  return 1;
                  ```
                MD,
            [
                ['return 1;', 3],
            ],
        ];

        yield 'stacked quotes and lists' => [
            <<<'MD'
                > ```php
                > return 1;
                > ```

                > > ```php
                > > return 2;
                > > ```

                > - ```php
                >   return 3;
                >   ```
                MD,
            [
                ['return 1;', 1],
                ['return 2;', 5],
                ['return 3;', 9],
            ],
        ];

        yield 'a tab inside an item' => [
            <<<MD
                - An item:

                \t```php
                \treturn 1;
                \t```
                MD,
            [
                ['return 1;', 3],
            ],
        ];

        yield 'a tab at the start of a page' => [
            <<<MD
                \t```php
                \treturn 1;
                \t```
                MD,
            [],
        ];

        yield 'an unclosed fence ending with its quote' => [
            <<<'MD'
                > ```php
                > return 1;
                After the quote.
                MD,
            [
                ['return 1;', 1],
            ],
        ];

        yield 'an unclosed fence ending with the page' => [
            <<<'MD'
                ```php
                return 1;
                MD,
            [
                ['return 1;', 1],
            ],
        ];

        yield 'an HTML comment' => [
            <<<'MD'
                <!--
                ```php
                return 1;
                ```
                -->
                MD,
            [],
        ];

        yield 'an HTML block ended by a blank line' => [
            <<<'MD'
                <details>

                ```php
                return 1;
                ```

                </details>
                MD,
            [
                ['return 1;', 3],
            ],
        ];

        yield 'an HTML block still open' => [
            <<<'MD'
                <details>
                ```php
                return 1;
                ```
                </details>
                MD,
            [],
        ];

        yield 'a list item ended by a paragraph' => [
            <<<'MD'
                - An item

                A paragraph.

                    ```php
                    return 1;
                    ```
                MD,
            [],
        ];

        yield 'a fence shown inside a longer fence' => [
            <<<'MD'
                ````md
                ```php
                return 1;
                ```
                ````
                MD,
            [],
        ];

        yield 'a longer closing run' => [
            <<<'MD'
                ```php
                return 1;
                `````
                After the fence.
                MD,
            [
                ['return 1;', 1],
            ],
        ];

        yield 'a closing run with an info string' => [
            <<<'MD'
                ```php
                return 1;
                ```php
                ```
                MD,
            [
                [
                    <<<'PHP'
                        return 1;
                        ```php
                        PHP,
                    1,
                ],
            ],
        ];

        yield 'the skip marker' => [
            <<<MD
                {$skipMarker}

                ```php
                return 1;
                ```

                {$skipMarker}
                A paragraph.

                ```php
                return 2;
                ```
                MD,
            [
                ['return 2;', 10],
            ],
        ];

        yield 'a docblock inside a fence' => [
            <<<'MD'
                ```php
                /**
                 * ```php
                 * return 1;
                 * ```
                 */
                function acme(): void {}
                ```
                MD,
            [
                [
                    <<<'PHP'
                        /**
                         * ```php
                         * return 1;
                         * ```
                         */
                        function acme(): void {}
                        PHP,
                    1,
                ],
            ],
        ];
    }

    /**
     * @param list<array{string, int}> $expectedFences
     */
    #[DataProvider('provideADocCommentYieldsThePhpFencesOfItsMarkdownCases')]
    public function testADocCommentYieldsThePhpFencesOfItsMarkdown(string $docComment, array $expectedFences): void
    {
        self::assertSame($expectedFences, self::getPhpCodesWithLineIndexes(FencedCode::fromDocComment($docComment)));
    }

    /**
     * @return iterable<string, array{string, list<array{string, int}>}>
     */
    public static function provideADocCommentYieldsThePhpFencesOfItsMarkdownCases(): iterable
    {
        yield 'the comment decoration' => [
            <<<'PHP'
                    /**
                     * ```js
                     * export default getConfig();
                     * ```
                     *
                     * @example
                     * ```php
                     * $builder = PhpStan::getBuilder();
                     *
                     * $builder->build();
                     * ```
                     */
                PHP,
            [
                [
                    <<<'PHP'
                        $builder = PhpStan::getBuilder();

                        $builder->build();
                        PHP,
                    7,
                ],
            ],
        ];

        yield 'a fence inside a list' => [
            <<<'PHP'
                /**
                 * - An item:
                 *
                 *   ```php
                 *   return 1;
                 *   ```
                 */
                PHP,
            [
                ['return 1;', 4],
            ],
        ];

        yield 'a fence shown inside a longer fence' => [
            <<<'PHP'
                /**
                 * ````md
                 * ```php
                 * return 1;
                 * ```
                 * ````
                 */
                PHP,
            [],
        ];

        yield 'no space after the star' => [
            <<<'PHP'
                /**
                 *```php
                 *return 1;
                 *```
                 */
                PHP,
            [
                ['return 1;', 2],
            ],
        ];
    }

    /**
     * @param Closure(string $text): list<FencedCode> $fencedCodeFactory
     */
    #[DataProvider('provideReplacingAFenceReappliesItsPrefixCases')]
    public function testReplacingAFenceReappliesItsPrefix(
        Closure $fencedCodeFactory,
        string $text,
        string $code,
        string $expectedText,
    ): void {
        $fencedCodes = $fencedCodeFactory($text);

        self::assertArrayHasKey(0, $fencedCodes);
        self::assertSame($expectedText, $fencedCodes[0]->replaceIn($text, $code));
    }

    /**
     * @return iterable<string, array{Closure(string $text): list<FencedCode>, string, string, string}>
     */
    public static function provideReplacingAFenceReappliesItsPrefixCases(): iterable
    {
        yield 'a list item' => [
            FencedCode::fromMarkdown(...),
            <<<'MD'
                - An item:

                  ```php
                  return 1;
                  ```
                MD,
            <<<'PHP'
                return [

                    1,
                ];
                PHP,
            <<<'MD'
                - An item:

                  ```php
                  return [

                      1,
                  ];
                  ```
                MD,
        ];

        yield 'a quote with an empty line' => [
            FencedCode::fromMarkdown(...),
            <<<'MD'
                > ```php
                > return 1;
                > ```
                MD,
            <<<'PHP'
                return [

                    1,
                ];
                PHP,
            <<<'MD'
                > ```php
                > return [
                >
                >     1,
                > ];
                > ```
                MD,
        ];

        yield 'a docblock with an empty line' => [
            FencedCode::fromDocComment(...),
            <<<'PHP'
                    /**
                     * ```php
                     * return 1;
                     * ```
                     */
                PHP,
            <<<'PHP'
                return [

                    1,
                ];
                PHP,
            <<<'PHP'
                    /**
                     * ```php
                     * return [
                     *
                     *     1,
                     * ];
                     * ```
                     */
                PHP,
        ];

        yield 'a tab-indented fence' => [
            FencedCode::fromMarkdown(...),
            <<<MD
                - An item:

                \t```php
                \treturn 1;
                \t```
                MD,
            'return 2;',
            <<<MD
                - An item:

                \t```php
                \treturn 2;
                \t```
                MD,
        ];
    }

    /**
     * @param list<FencedCode> $fencedCodes
     *
     * @return list<array{string, int}>
     */
    private static function getPhpCodesWithLineIndexes(array $fencedCodes): array
    {
        return array_map(
            static fn (FencedCode $fencedCode): array => [
                $fencedCode->code,
                $fencedCode->firstLineIndex,
            ],
            FencedCode::filterByLanguage($fencedCodes, FencedCode::LANGUAGE_PHP),
        );
    }
}
