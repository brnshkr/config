<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Mate;

use Brnshkr\Config\Mate\Support\Project;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * @internal
 */
#[CoversNothing]
final class ProjectTest extends TestCase
{
    /**
     * Every control-sequence form a quality tool can emit, mapped to the output
     * that has to survive the sanitizing pass.
     */
    private const array CONTROL_SEQUENCE_CASES = [
        'sgr reset'                        => ["\x1B[0m", ''],
        'sgr with multiple parameters'     => ["\x1B[31;1mred\x1B[39;22m", 'red'],
        'sgr with true color parameters'   => ["\x1B[38;2;255;128;0mx\x1B[0m", 'x'],
        'cursor column'                    => ["\x1B[1Gtext", 'text'],
        'erase line'                       => ["\x1B[2Ktext", 'text'],
        'erase display'                    => ["\x1B[2Jtext", 'text'],
        'final byte without parameters'    => ["\x1B[Atext", 'text'],
        'cursor position'                  => ["\x1B[1;2Htext", 'text'],
        'private mode toggle'              => ["\x1B[?25lspinner\x1B[?25h", 'spinner'],
        'intermediate byte'                => ["\x1B[1 qtext", 'text'],
        'phpstan progress bar'             => ["\x1B[1G\x1B[2K 118/118 [▓▓] 100% < 1 sec", '118/118 [▓▓] 100% < 1 sec'],
        'pest summary line'                => ["\x1B[90mTests:\x1B[39m \x1B[32;1m48 passed\x1B[39;22m", 'Tests: 48 passed'],
        'hyperlink with bell terminator'   => ["\x1B]8;;file:///tmp/x\x07label\x1B]8;;\x07", 'label'],
        'hyperlink with string terminator' => ["\x1B]8;;file:///tmp/x\x1B\\label\x1B]8;;\x1B\\", 'label'],
        'hyperlink with parameters'        => ["\x1B]8;id=1;https://example.com\x07link\x1B]8;;\x07", 'link'],
        'window title'                     => ["\x1B]0;title\x07text", 'text'],
        'null byte'                        => ["a\x00b", 'ab'],
        'bell'                             => ["a\x07b", 'ab'],
        'backspace'                        => ["a\x08b", 'ab'],
        'vertical tab and form feed'       => ["a\x0Bb\x0Cc", 'abc'],
        'delete'                           => ["a\x7Fb", 'ab'],
        'dangling escape'                  => ["text\x1B", 'text'],
        'truncated hyperlink'              => ["a\x1B]8;;https://example.com", 'a]8;;https://example.com'],
        'tab, newline and carriage return' => ["a\tb\nc\rd", "a\tb\nc\rd"],
        'multibyte characters'             => ['✓ ⨯ 118/118 ´', '✓ ⨯ 118/118 ´'],
        'bytes above the ascii range'      => ["\u{009B}text", "\u{009B}text"],
        'plain text'                       => ['No errors', 'No errors'],
    ];

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    public function testStripsControlSequencesFromOutput(): void
    {
        foreach (self::CONTROL_SEQUENCE_CASES as $name => [$input, $expected]) {
            self::assertSame($expected, Project::run(['cat'], input: $input)['output'], $name);
        }
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    public function testEncodesOutputContainingControlSequences(): void
    {
        $result = Project::run(['cat'], input: "\x1B[1G\x1B[2K 118/118\x00 100%");

        self::assertStringContainsString('118/118 100%', Project::encode($result));
    }
}
