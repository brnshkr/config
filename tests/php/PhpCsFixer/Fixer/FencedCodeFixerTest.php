<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpCsFixer\Fixer;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\FencedCode;
use Brnshkr\Config\FileFinder;
use Brnshkr\Config\Json;
use Brnshkr\Config\Module;
use Brnshkr\Config\Package;
use Brnshkr\Config\PhpCsFixer;
use Brnshkr\Config\PhpCsFixer\Fixer\FencedCodeFixer;
use Brnshkr\Config\PhpCsFixer\Fixer\MarkdownSafeFixer;
use Brnshkr\Config\ProjectDirectory;
use Brnshkr\Config\Str;
use Brnshkr\Config\TwigCsFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

use function array_find;
use function sprintf;

/**
 * @internal
 */
#[CoversClass(FencedCodeFixer::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(FencedCode::class)]
#[UsesClass(FileFinder::class)]
#[UsesClass(Json::class)]
#[UsesClass(MarkdownSafeFixer::class)]
#[UsesClass(Module::class)]
#[UsesClass(Package::class)]
#[UsesClass(PhpCsFixer::class)]
#[UsesClass(ProjectDirectory::class)]
#[UsesClass(Str::class)]
#[UsesClass(TwigCsFixer::class)]
final class FencedCodeFixerTest extends TestCase
{
    /**
     * @param array{
     *     excluded_rules?: list<string>,
     * } $configuration
     */
    #[DataProvider('provideAFenceIsFormattedWithTheConfiguredRulesCases')]
    public function testAFenceIsFormattedWithTheConfiguredRules(
        string $fileName,
        string $code,
        string $expectedCode,
        array $configuration = [],
    ): void {
        $fencedCodeFixer = array_find(
            PhpCsFixer::getConfig()->getCustomFixers(),
            static fn (object $fixer): bool => $fixer instanceof FencedCodeFixer,
        );

        self::assertInstanceOf(FencedCodeFixer::class, $fencedCodeFixer);

        $fencedCodeFixer->configure($configuration);

        $tokens = Tokens::fromCode($code);

        $fencedCodeFixer->fix(new SplFileInfo($fileName, '', $fileName), $tokens);

        self::assertSame($expectedCode, $tokens->generateCode());
    }

    /**
     * @return iterable<string, array{
     *     0: string,
     *     1: string,
     *     2: string,
     *     3?: array{
     *         excluded_rules?: list<string>,
     *     },
     * }>
     */
    public static function provideAFenceIsFormattedWithTheConfiguredRulesCases(): iterable
    {
        $skipMarker = sprintf('<!-- %s-skip -->', ComposerJson::forThisLibrary()->getPackageOrganization());

        yield 'a docblock fence' => [
            'User.php',
            <<<'PHP'
                <?php

                /**
                 * ```php
                 * $user=new User( 'acme' );
                 * ```
                 */
                function acme(): void {}

                PHP,
            <<<'PHP'
                <?php

                /**
                 * ```php
                 * $user = new User('acme');
                 * ```
                 */
                function acme(): void {}

                PHP,
        ];

        yield 'names and file-level rules left alone' => [
            'User.php',
            <<<'PHP'
                <?php

                /**
                 * ```php
                 * $files = FileFinder::get();
                 * ```
                 */
                function acme(): void {}

                PHP,
            <<<'PHP'
                <?php

                /**
                 * ```php
                 * $files = FileFinder::get();
                 * ```
                 */
                function acme(): void {}

                PHP,
        ];

        yield 'a stub formatted like code' => [
            'User.php',
            <<<'PHP'
                <?php

                /**
                 * ```php
                 * interface UserRepositoryInterface
                 * {
                 * }
                 * ```
                 */
                function acme(): void {}

                PHP,
            <<<'PHP'
                <?php

                /**
                 * ```php
                 * interface UserRepositoryInterface {}
                 * ```
                 */
                function acme(): void {}

                PHP,
        ];

        yield 'an option adding to the excluded rules' => [
            'User.php',
            <<<'PHP'
                <?php

                /**
                 * ```php
                 * $user=new User('acme');
                 * ```
                 */
                function acme(): void {}

                PHP,
            <<<'PHP'
                <?php

                /**
                 * ```php
                 * $user=new User('acme');
                 * ```
                 */
                function acme(): void {}

                PHP,
            [
                'excluded_rules' => ['binary_operator_spaces'],
            ],
        ];

        yield 'a Markdown fence' => [
            'User.md',
            <<<'MD'
                # User

                ```php
                $user=new User( 'acme' );
                ```

                Hard break
                kept.

                MD,
            <<<'MD'
                # User

                ```php
                $user = new User('acme');
                ```

                Hard break
                kept.

                MD,
        ];

        yield 'a fence in a .markdown page' => [
            'User.markdown',
            <<<'MD'
                # User

                ```php
                $user=new User( 'acme' );
                ```

                MD,
            <<<'MD'
                # User

                ```php
                $user = new User('acme');
                ```

                MD,
        ];

        yield 'a Twig fence beside a PHP one' => [
            'User.md',
            <<<'MD'
                # User

                ```twig
                {%if user.isActive%}{{user.name|upper}}{%endif%}
                ```

                ```php
                $user=new User( 'acme' );
                ```

                MD,
            <<<'MD'
                # User

                ```twig
                {% if user.isActive %}{{ user.name|upper }}{% endif %}
                ```

                ```php
                $user = new User('acme');
                ```

                MD,
        ];

        yield 'a Twig docblock fence' => [
            'User.php',
            <<<'PHP'
                <?php

                /**
                 * ```twig
                 * {{user.name}}
                 * ```
                 */
                function acme(): void {}

                PHP,
            <<<'PHP'
                <?php

                /**
                 * ```twig
                 * {{ user.name }}
                 * ```
                 */
                function acme(): void {}

                PHP,
        ];

        yield 'an untokenizable Twig fence' => [
            'User.md',
            <<<'MD'
                ```twig
                {% if user %}{{ user.name
                ```

                MD,
            <<<'MD'
                ```twig
                {% if user %}{{ user.name
                ```

                MD,
        ];

        yield 'a skipped Markdown fence' => [
            'User.md',
            <<<MD
                # User

                {$skipMarker}
                ```php
                \$user=new User( 'acme' );
                ```

                MD,
            <<<MD
                # User

                {$skipMarker}
                ```php
                \$user=new User( 'acme' );
                ```

                MD,
        ];

        yield 'an unparsable Markdown fence' => [
            'User.md',
            <<<'MD'
                # User

                ```php
                $user=new User(;
                ```

                MD,
            <<<'MD'
                # User

                ```php
                $user=new User(;
                ```

                MD,
        ];

        yield 'a docblock inside a Markdown fence' => [
            'User.md',
            <<<'MD'
                ```php
                /**
                 * Creates a user.
                 *
                 * ```php
                 * $user=new User( 'acme' );
                 * ```
                 */
                function acme(): void {}
                ```

                MD,
            <<<'MD'
                ```php
                /**
                 * Creates a user.
                 *
                 * ```php
                 * $user = new User('acme');
                 * ```
                 */
                function acme(): void {}
                ```

                MD,
        ];

        yield 'the inline HTML of a PHP template' => [
            'user.html.php',
            <<<'PHP'
                <p>
                ```php
                $user=new User( 'acme' );
                ```
                </p>

                PHP,
            <<<'PHP'
                <p>
                ```php
                $user=new User( 'acme' );
                ```
                </p>

                PHP,
        ];
    }
}
