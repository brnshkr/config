<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpCsFixer\Fixer;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Json;
use Brnshkr\Config\PhpCsFixer\Fixer\MarkdownSafeFixer;
use Brnshkr\Config\Str;
use PhpCsFixer\Fixer\StringNotation\NoTrailingWhitespaceInStringFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @internal
 */
#[CoversClass(MarkdownSafeFixer::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(Json::class)]
#[UsesClass(Str::class)]
final class MarkdownSafeFixerTest extends TestCase
{
    public function testTheWrappedFixerRunsUnderTheOrganizationsNameEverywhereButMarkdown(): void
    {
        $markdownSafeFixer = new MarkdownSafeFixer(new NoTrailingWhitespaceInStringFixer());
        $organization      = Str::upperFirst(ComposerJson::forThisLibrary()->getPackageOrganization());
        $tokens            = Tokens::fromCode("<?php\n\$text = 'trailing  \nspaces';\n");

        $markdownSafeFixer->fix(new SplFileInfo('User.php', '', 'User.php'), $tokens);

        self::assertSame($organization . '/no_trailing_whitespace_in_string', $markdownSafeFixer->getName());
        self::assertTrue($markdownSafeFixer->supports(new SplFileInfo('User.php', '', 'User.php')));
        self::assertFalse($markdownSafeFixer->supports(new SplFileInfo('User.md', '', 'User.md')));
        self::assertFalse($markdownSafeFixer->supports(new SplFileInfo('User.markdown', '', 'User.markdown')));
        self::assertSame("<?php\n\$text = 'trailing\nspaces';\n", $tokens->generateCode());
    }
}
