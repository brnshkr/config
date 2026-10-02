<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

use function mb_substr_count;
use function sprintf;

/**
 * @internal
 */
#[CoversNothing]
final class OutputTest extends TestCase
{
    use MakeTrait;

    private const string SPINNER_DIRECTORY = __DIR__ . '/../Fixtures/Make/Spinner';

    public function testUnsupportedEditorGivenDeliberatelyReportsError(): void
    {
        $result = $this->runMake(['help', 'EDITOR=nano'], doExpectFailure: true);

        self::assertStringContainsString('Unknown editor', $result);
        self::assertStringContainsString('"nano"', $result);
    }

    public function testUnsupportedEditorFromTheEnvironmentIsIgnored(): void
    {
        $result = $this->runMakeHelp(env: ['EDITOR' => 'vim']);

        self::assertStringNotContainsString('Unknown editor', $result);
    }

    public function testAnsiEscapesAreEmittedWhenColorsAreEnabled(): void
    {
        $withColors    = $this->runMakeHelp(env: self::COLORED_ENV);
        $withoutColors = $this->runMakeHelp();

        self::assertStringContainsString("\033[", $withColors);
        self::assertStringNotContainsString("\033[", $withoutColors);
    }

    public function testEditorHyperlinksMatchSelectedEditor(): void
    {
        $vscode = $this->runMakeHelp(['vvv'], ['EDITOR' => 'vscode', ...self::COLORED_ENV]);

        $vscodeWsl = $this->runMakeHelp(['vvv'], [
            'EDITOR'          => 'vscode',
            'WSL_DISTRO_NAME' => 'Ubuntu',
            ...self::COLORED_ENV,
        ]);

        $phpstorm = $this->runMakeHelp(['vvv'], ['EDITOR' => 'phpstorm', ...self::COLORED_ENV]);

        self::assertStringContainsString("\033]8;;vscode://file/", $vscode);
        self::assertStringContainsString("\033]8;;vscode://vscode-remote/wsl+Ubuntu/", $vscodeWsl);
        self::assertStringContainsString("\033]8;;phpstorm://open?file=", $phpstorm);
    }

    public function testOnlyTraceEchoesRecipeLines(): void
    {
        $withTrace    = $this->runMakeHelp(env: ['TRACE' => '1']);
        $withDebug    = $this->runMakeHelp(env: ['DEBUG' => '1']);
        $withoutFlags = $this->runMakeHelp();

        self::assertStringContainsString('_CURDIR=', $withTrace);
        self::assertStringNotContainsString('_CURDIR=', $withDebug);
        self::assertStringNotContainsString('_CURDIR=', $withoutFlags);
    }

    public function testDebugEchoesTheCommandBehindTheSpinnerAndTraceItsWrapper(): void
    {
        $withDebug    = $this->runMake(['spin'], ['DEBUG' => '1'], self::SPINNER_DIRECTORY);
        $withTrace    = $this->runMake(['spin'], ['TRACE' => '1'], self::SPINNER_DIRECTORY);
        $withoutFlags = $this->runMake(['spin'], directory: self::SPINNER_DIRECTORY);

        self::assertStringContainsString("'ran\\n'; exit 0\n", $withDebug);
        self::assertStringNotContainsString('spin_command', $withDebug);
        self::assertStringContainsString('spin_command', $withTrace);
        self::assertSame("ran\n", $withoutFlags);
    }

    #[Group('tty')]
    public function testACommandThatExitsItselfRunsOnceBehindTheSpinner(): void
    {
        $output = $this->runMakeOnATty(['spin'], self::SPINNER_DIRECTORY);

        self::assertSame(1, mb_substr_count($output, 'ran'));
    }

    #[Group('tty')]
    public function testAnInterruptStopsTheCommandBehindTheSpinnerWithoutRerunningIt(): void
    {
        $inputStream = new InputStream();

        $process = new Process([
            'script',
            '-qfc',
            sprintf('make --no-print-directory -C %s spin-until-interrupted', self::SPINNER_DIRECTORY),
            '/dev/null',
        ], env: [
            ...self::getBaselineEnvironment(),
            'NO_COLOR' => '',
        ], input: $inputStream, timeout: 20);

        $process->start();
        $process->waitUntil(static fn (string $type, string $output): bool => Str::contains($output, 'started'));
        $inputStream->write("\x03");
        $inputStream->close();
        $process->wait();

        self::assertSame(1, mb_substr_count($process->getOutput(), 'started'));
    }

    public function testTraceEchoesTheGuardsThatDebugLeavesOut(): void
    {
        $withDebug = $this->runMake(
            ['phpstan'],
            ['DEBUG' => '1'],
            directory: __DIR__ . '/../Fixtures/Make/Guard',
            doExpectFailure: true,
        );

        $withTrace = $this->runMake(
            ['phpstan'],
            ['TRACE' => '1'],
            directory: __DIR__ . '/../Fixtures/Make/Guard',
            doExpectFailure: true,
        );

        self::assertStringNotContainsString('test -r', $withDebug);
        self::assertStringContainsString('test -r', $withTrace);
    }

    public function testTheErrorCodeIsTheOneTheProjectChose(): void
    {
        $result = $this->runMake(
            ['phpstan'],
            ['BRNSHKR_CONFIG_ERROR_CODE' => '7'],
            directory: __DIR__ . '/../Fixtures/Make/Guard',
            doExpectFailure: true,
        );

        self::assertStringContainsString('Error 7', $result);
        self::assertStringNotContainsString('Error 69', $result);
    }

    public function testEditorLinksFollowTheTemplateTheProjectGives(): void
    {
        $result = $this->runMakeHelp(['vvv'], [
            'EDITOR'     => 'vscode',
            'EDITOR_URL' => 'acme://open/{file}#{line}',
            ...self::COLORED_ENV,
        ]);

        self::assertStringContainsString("\033]8;;acme://open/", $result);
        self::assertStringNotContainsString('vscode://file/', $result);
    }

    public function testEditorLinksNameTheProjectDirectoryOnce(): void
    {
        $helpDirectory = self::getRealPath(self::FIXTURES_DIRECTORY);

        $result = $this->runMakeHelp(['vvv'], [
            'EDITOR_URL' => 'acme://open/{cwd}/{file}#{line}',
            ...self::COLORED_ENV,
        ]);

        self::assertStringContainsString('acme://open/' . $helpDirectory . '/.local/Makefile#', $result);
        self::assertStringNotContainsString($helpDirectory . '/' . $helpDirectory, $result);
    }

    public function testOutputThatIsNotATerminalGetsNoProgressLine(): void
    {
        $output = $this->runMake(['group-pairs'], directory: __DIR__ . '/../Fixtures/Make/Group');

        self::assertStringStartsWith('2  acme.first', $output);
        self::assertStringNotContainsString('Running', $output);
    }
}
