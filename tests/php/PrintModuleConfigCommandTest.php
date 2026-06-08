<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Composer\Command\CommandProvider;
use Brnshkr\Config\Composer\Command\PrintModuleConfigCommand;
use Brnshkr\Config\Module;
use Composer\Console\Application;
use Exception;
use LogicException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function getcwd;
use function preg_quote;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal
 */
#[CoversClass(PrintModuleConfigCommand::class)]
final class PrintModuleConfigCommandTest extends TestCase
{
    use MatchesSnapshots;

    private Application $application;

    /**
     * @throws LogicException
     */
    #[Override]
    protected function setUp(): void
    {
        $application = new Application();

        $application->setAutoExit(false);
        $application->addCommands(new CommandProvider()->getCommands());

        $this->application = $application;
    }

    /**
     * @throws Exception
     */
    public function testPrintsExpectedPhpCsFixerConfig(): void
    {
        $this->assertMatchesJsonSnapshot($this->runForModule(Module::NAME_PHP_CS_FIXER));
    }

    /**
     * @throws Exception
     */
    public function testPrintsExpectedPhpStanConfig(): void
    {
        $this->assertMatchesJsonSnapshot($this->runForModule(Module::NAME_PHP_STAN));
    }

    /**
     * @throws Exception
     */
    public function testPrintsExpectedRectorConfig(): void
    {
        $this->assertMatchesJsonSnapshot($this->runForModule(Module::NAME_RECTOR));
    }

    /**
     * @throws Exception
     */
    public function testPrintsExpectedTwigCsFixerConfig(): void
    {
        $this->assertMatchesJsonSnapshot($this->runForModule(Module::NAME_TWIG_CS_FIXER));
    }

    /**
     * @throws Exception
     */
    public function testAutoDetectsModuleFromPath(): void
    {
        $output = $this->runCommand(['--path' => 'conf/php-cs-fixer.dist.php']);

        $this->assertMatchesJsonSnapshot($output);
    }

    /**
     * @throws Exception
     */
    public function testErrorsWhenModuleAndPathBothOmitted(): void
    {
        $output = $this->runCommand([], isSuccessExpected: false);

        self::assertStringContainsString(
            'Either the "module" argument or the --path option must be provided.',
            $output,
        );
    }

    /**
     * @throws Exception
     */
    public function testErrorsOnUnknownModule(): void
    {
        $output = $this->runCommand(['module' => 'unknown-tool'], isSuccessExpected: false);

        self::assertStringContainsString('Unknown module "unknown-tool"', $output);
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function testErrorsOnMissingConfigPath(): void
    {
        $output = $this->runCommand(
            ['--path' => 'conf/does-not-exist.dist.php'],
            isSuccessExpected: false,
        );

        self::assertStringContainsString('does not exist', $output);
    }

    /**
     * @throws Exception
     */
    private function runForModule(string $module): string
    {
        return $this->runCommand(['module' => $module]);
    }

    /**
     * @param array<string, string> $arguments
     *
     * @throws Exception
     */
    private function runCommand(array $arguments, bool $isSuccessExpected = true): string
    {
        $arrayInput = new ArrayInput([
            'command' => new PrintModuleConfigCommand()->getName(),
            ...$arguments,
        ]);

        $bufferedOutput = new BufferedOutput();
        $exitCode       = $this->application->run($arrayInput, $bufferedOutput);

        if ($isSuccessExpected) {
            self::assertSame(0, $exitCode);
        } else {
            self::assertNotSame(0, $exitCode);
        }

        return s($bufferedOutput->fetch())
            ->replaceMatches(sprintf('/%s/', preg_quote(getcwd() ?: '.', '/')), '.')
            ->toString()
        ;
    }
}
