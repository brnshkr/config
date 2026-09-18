<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Composer\Command\CommandProvider;
use Brnshkr\Config\Composer\Command\PrintModuleConfigCommand;
use Brnshkr\Config\Module;
use Brnshkr\Config\Str;
use Brnshkr\Config\Testing\JsonSnapshotDriver;
use Composer\Console\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function getcwd;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal
 */
#[CoversClass(PrintModuleConfigCommand::class)]
#[UsesClass(JsonSnapshotDriver::class)]
final class PrintModuleConfigCommandTest extends TestCase
{
    use MatchesSnapshots;

    public function testPrintsExpectedPhpCsFixerConfig(): void
    {
        $this->assertMatchesSnapshot($this->runForModule(Module::NAME_PHP_CS_FIXER), new JsonSnapshotDriver());
    }

    public function testPrintsExpectedPhpStanConfig(): void
    {
        $this->assertMatchesSnapshot($this->runForModule(Module::NAME_PHP_STAN), new JsonSnapshotDriver());
    }

    public function testPrintsExpectedRectorConfig(): void
    {
        $this->assertMatchesSnapshot($this->runForModule(Module::NAME_RECTOR), new JsonSnapshotDriver());
    }

    public function testPrintsExpectedTwigCsFixerConfig(): void
    {
        $this->assertMatchesSnapshot($this->runForModule(Module::NAME_TWIG_CS_FIXER), new JsonSnapshotDriver());
    }

    public function testAutoDetectsModuleFromPath(): void
    {
        self::assertSame(
            $this->runForModule(Module::NAME_PHP_CS_FIXER),
            $this->runCommand(['--path' => 'conf/php-cs-fixer.dist.php']),
        );
    }

    public function testErrorsWhenModuleAndPathBothOmitted(): void
    {
        $output = $this->runCommand([], isSuccessExpected: false);

        self::assertStringContainsString(
            'Either the "module" argument or the --path option must be provided.',
            $output,
        );
    }

    public function testErrorsOnUnknownModule(): void
    {
        $output = $this->runCommand(['module' => 'unknown-tool'], isSuccessExpected: false);

        self::assertStringContainsString('Unknown module "unknown-tool"', $output);
    }

    public function testErrorsOnTypeMismatch(): void
    {
        $output = $this->runCommand(
            [
                'module' => Module::NAME_PHP_STAN,
                '--path' => 'conf/php-cs-fixer.dist.php',
            ],
            isSuccessExpected: false,
        );

        self::assertStringContainsString(
            sprintf('but module "%s" expects', Module::NAME_PHP_STAN),
            $output,
        );
    }

    public function testErrorsOnMissingConfigPath(): void
    {
        $output = $this->runCommand(
            ['--path' => 'conf/does-not-exist.dist.php'],
            isSuccessExpected: false,
        );

        self::assertStringContainsString('does not exist', $output);
    }

    private function runForModule(string $module): string
    {
        return $this->runCommand(['module' => $module]);
    }

    /**
     * @param array<string, string> $arguments
     */
    private function runCommand(array $arguments, bool $isSuccessExpected = true): string
    {
        $application = new Application();

        $application->setAutoExit(false);
        $application->addCommands(new CommandProvider()->getCommands());

        $arrayInput = new ArrayInput([
            'command' => new PrintModuleConfigCommand()->getName(),
            ...$arguments,
        ]);

        $bufferedOutput = new BufferedOutput();
        $exitCode       = $application->run($arrayInput, $bufferedOutput);

        if ($isSuccessExpected) {
            self::assertSame(0, $exitCode);
        } else {
            self::assertNotSame(0, $exitCode);
        }

        return s($bufferedOutput->fetch())
            ->replaceMatches(sprintf('/%s/', Str::quoteRegex(getcwd() ?: '.')), '.')
            ->toString()
        ;
    }
}
