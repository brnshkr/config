<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Tests\Make\Trait\ContainerTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

use function sprintf;

/**
 * @internal
 */
#[CoversNothing]
final class ContainerTest extends TestCase
{
    use ContainerTrait;

    private const string SERVICE       = 'php-app';
    private const string OTHER_SERVICE = 'alt';
    private const string BUN_DIRECTORY = __DIR__ . '/../Fixtures/Make/Bun';

    private const array EXPECTED_LOCATIONS_BY_MODE = [
        self::MODE_NATIVE => [
            'make' => 'host',
            'tool' => 'host',
        ],
        self::MODE_EXEC => [
            'make' => 'host',
            'tool' => 'container',
        ],
        self::MODE_RUN => [
            'make' => 'host',
            'tool' => 'container',
        ],
        self::MODE_INSIDE => [
            'make' => 'container',
            'tool' => 'container',
        ],
    ];

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideEveryModeCases')]
    public function testAToolRunsWhereTheModeSays(string $mode): void
    {
        $output = $this->runMakeIn($mode, ['where']);

        self::assertStringContainsString('make=' . self::EXPECTED_LOCATIONS_BY_MODE[$mode]['make'], $output);
        self::assertStringContainsString('tool=' . self::EXPECTED_LOCATIONS_BY_MODE[$mode]['tool'], $output);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideEveryModeCases')]
    public function testAToolNamedForAnotherServiceRunsThere(string $mode): void
    {
        $output = $this->runMakeIn($mode, ['where-alt']);

        self::assertStringContainsString('alt=' . ($mode === self::MODE_NATIVE ? 'host' : 'container'), $output);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideEveryModeCases')]
    public function testMakeKnowsWhereTheToolsSeeTheProject(string $mode): void
    {
        $output = $this->runMakeIn($mode, ['show']);

        $projectPathTheToolsSee = $mode === self::MODE_EXEC || $mode === self::MODE_RUN
            ? self::getContainerPath()
            : self::getRealPath(self::CONTAINER_DIRECTORY);

        self::assertStringContainsString('app-dir=' . $projectPathTheToolsSee, $output);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideEveryModeCases')]
    public function testTheEnvironmentFilesDecideWhatAToolSees(string $mode): void
    {
        $output = $this->runMakeIn($mode, ['show']);

        self::assertStringContainsString('stage=dev', $output);
        self::assertStringContainsString('declared=from-the-file', $output);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideEveryModeCases')]
    public function testAStageGoalReachesTheTool(string $mode): void
    {
        $output = $this->runMakeIn($mode, ['test-show']);

        self::assertStringContainsString('stage=test', $output);
        self::assertStringContainsString('stage-file=test', $output);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideEveryModeCases')]
    public function testTheHostEnvironmentStillWinsOverADeclaredKey(string $mode): void
    {
        $output = $this->runMakeIn($mode, ['show'], ['CONTAINER_FIXTURE_DECLARED' => 'from-the-host']);

        self::assertStringContainsString('declared=from-the-host', $output);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideEveryModeCases')]
    public function testACommandLineVariableReachesTheTool(string $mode): void
    {
        $output = $this->runMakeIn($mode, ['show', 'CONTAINER_FIXTURE_DECLARED=from-the-command-line']);

        self::assertStringContainsString('declared=from-the-command-line', $output);
    }

    /**
     * @return iterable<string, array{self::MODE_*}>
     */
    public static function provideEveryModeCases(): iterable
    {
        foreach ([self::MODE_NATIVE, ...self::CONTAINER_MODES] as $mode) {
            yield $mode => [$mode];
        }
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideHostModeCases')]
    public function testAnUndeclaredHostVariableStaysOnTheHost(string $mode): void
    {
        $output = $this->runMakeIn($mode, ['show'], ['CONTAINER_FIXTURE_UNDECLARED' => 'from-the-host']);

        self::assertStringContainsString("undeclared=\n", $output);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideHostModeCases')]
    public function testADeclaredValueStaysOffTheCommandLine(string $mode): void
    {
        $output = $this->runMakeIn($mode, ['-n', 'show']);

        self::assertStringContainsString('-e CONTAINER_FIXTURE_DECLARED', $output);
        self::assertStringNotContainsString('from-the-file', $output);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideHostModeCases')]
    public function testTheEditorUrlAToolSeesPointsAtTheHostCheckout(string $mode): void
    {
        $output = $this->runMakeIn($mode, ['show'], ['EDITOR_URL' => 'editor://{cwd}/{file}']);

        self::assertStringContainsString(
            sprintf('editor=editor://%s/{file}', self::getRealPath(self::CONTAINER_DIRECTORY)),
            $output,
        );
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideTheEditorUrlIsResolvedWhereTheToolItselfCanCases')]
    public function testTheEditorUrlIsResolvedWhereTheToolItselfCan(string $mode): void
    {
        $output = $this->runMakeIn($mode, ['show'], ['EDITOR_URL' => 'editor://{cwd}/{file}']);

        self::assertStringContainsString('editor=editor://{cwd}/{file}', $output);
    }

    /**
     * @return iterable<string, array{self::MODE_*}>
     */
    public static function provideTheEditorUrlIsResolvedWhereTheToolItselfCanCases(): iterable
    {
        foreach ([self::MODE_NATIVE, self::MODE_INSIDE] as $mode) {
            yield $mode => [$mode];
        }
    }

    public function testDockerAndPodmanMarkersAreBothDetected(): void
    {
        $markerDirectory = $this->createFixtureRoot();
        $dockerMarker    = $markerDirectory . '/.dockerenv';
        $podmanMarker    = $markerDirectory . '/run/.containerenv';
        $markerArguments = ['detect', '_CONTAINER_MARKERS=' . $dockerMarker . ' ' . $podmanMarker];
        $environment     = self::getContainerEnvironment();
        $filesystem      = new Filesystem();

        unset($environment['IS_IN_CONTAINER']);

        $host = $this->runMake($markerArguments, $environment, self::CONTAINER_DIRECTORY);

        $filesystem->dumpFile($dockerMarker, '');

        $docker = $this->runMake($markerArguments, $environment, self::CONTAINER_DIRECTORY);

        $filesystem->remove($dockerMarker);
        $filesystem->dumpFile($podmanMarker, '');

        $podman = $this->runMake($markerArguments, $environment, self::CONTAINER_DIRECTORY);

        self::assertStringContainsString('in-container=1', $docker);
        self::assertStringContainsString('in-container=1', $podman);
        self::assertStringNotContainsString('in-container=1', $host);
    }

    public function testAnExplicitSettingOverridesTheDetection(): void
    {
        $markerPath = $this->createFixtureRoot() . '/.dockerenv';

        new Filesystem()->dumpFile($markerPath, '');

        $inside = $this->runMake(
            ['detect', 'IS_IN_CONTAINER=', '_CONTAINER_MARKERS=' . $markerPath],
            self::getContainerEnvironment(),
            self::CONTAINER_DIRECTORY,
        );

        self::assertStringNotContainsString('in-container=1', $inside);
    }

    public function testATargetPerServiceRunsInThatService(): void
    {
        $service = $this->runMakeIn(self::MODE_EXEC, [self::SERVICE . '-exec', '--', 'printenv', 'STUB_SERVICE']);
        $other   = $this->runMakeIn(self::MODE_EXEC, [self::OTHER_SERVICE . '-exec', '--', 'printenv', 'STUB_SERVICE']);

        self::assertMatchesRegularExpression('/^' . self::SERVICE . '$/m', $service);
        self::assertMatchesRegularExpression('/^' . self::OTHER_SERVICE . '$/m', $other);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideHostModeCases')]
    public function testTheAppServiceTargetsEnterItTheWayTheModeSays(string $mode): void
    {
        $shell = $this->runMakeIn($mode, ['shell', '--', 'printenv', 'COLUMNS'], ['COLUMNS' => '123']);
        $exec  = $this->runMakeIn($mode, ['exec', '--', 'printenv', 'STUB_SERVICE']);

        self::assertStringContainsString('123', $shell);
        self::assertMatchesRegularExpression('/^' . self::SERVICE . '$/m', $exec);
    }

    public function testToolsFallBackToTheCliService(): void
    {
        $exec = $this->runMakeIn(
            self::MODE_EXEC,
            ['exec', '--', 'printenv', 'STUB_SERVICE'],
            ['APP_SERVICE' => 'web', 'CLI_SERVICE' => self::OTHER_SERVICE],
        );

        self::assertMatchesRegularExpression('/^' . self::OTHER_SERVICE . '$/m', $exec);
    }

    public function testExecHandsATargetNameToTheCommandRatherThanRunningIt(): void
    {
        $result = $this->runMakeIn(self::MODE_EXEC, ['exec', '--', 'echo', 'help']);

        self::assertMatchesRegularExpression('/^help$/m', $result);
        self::assertStringNotContainsString('Usage:', $result);
    }

    public function testExecWithoutACommandIsRefusedBeforeEnteringTheService(): void
    {
        foreach (['exec', self::SERVICE . '-exec'] as $target) {
            $result = $this->runMake(
                [$target],
                self::getContainerEnvironment(),
                self::CONTAINER_DIRECTORY,
                doExpectFailure: true,
            );

            self::assertStringContainsString(sprintf('`%s` needs a command', $target), $result);
            self::assertStringNotContainsString('PATH=', $result);
        }
    }

    public function testAnInspectorTheProjectDefinesRunsOnThisMachineWithoutANodeService(): void
    {
        $result = $this->runMake(
            ['eslint-inspect', 'ESLINT_CONFIG_INSPECTOR=echo inspector', 'ESLINT_CONFIG=package.json'],
            self::getContainerEnvironment(self::BUN_DIRECTORY),
            self::BUN_DIRECTORY,
        );

        self::assertSame("inspector --config package.json --port 7777\n", $result);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideHostModeCases')]
    public function testAToolWritesToATerminalExactlyWhenMakeDoes(string $mode): void
    {
        $piped = $this->runMakeIn($mode, ['terminal']);

        $process = self::createMakeProcess(
            ['terminal', ...($mode === self::MODE_RUN ? ['APP_SERVICE_MODE=run'] : [])],
            self::getContainerEnvironment(),
            self::CONTAINER_DIRECTORY,
        );

        $process->setPty(true);
        $process->mustRun();

        self::assertStringContainsString('tool=pipe', $piped);
        self::assertStringContainsString('tool=terminal', $process->getOutput());
    }

    /**
     * @return iterable<string, array{self::MODE_*}>
     */
    public static function provideHostModeCases(): iterable
    {
        foreach ([self::MODE_EXEC, self::MODE_RUN] as $mode) {
            yield $mode => [$mode];
        }
    }
}
