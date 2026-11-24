<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Tests\Make\Trait\ContainerTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

use function sprintf;

/**
 * @internal
 */
#[CoversNothing]
final class ContainerTest extends TestCase
{
    use ContainerTrait;

    private const string SERVICE                       = 'php-app';
    private const string OTHER_SERVICE                 = 'alt';
    private const string BUN_DIRECTORY                 = __DIR__ . '/../Fixtures/Make/Bun';
    private const string COMPOSE_SEARCH_DIRECTORY      = __DIR__ . '/../Fixtures/Make/ComposeSearch';
    private const string COMPOSE_ENVIRONMENT_DIRECTORY = __DIR__ . '/../Fixtures/Make/ComposeEnvironment';

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
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideEveryModeCases')]
    public function testAToolIsAskedNotToTrackUnlessAFalseValueSaysOtherwise(string $mode): void
    {
        $defaultOutput  = $this->runMakeIn($mode, ['show']);
        $trackingOutput = $this->runMakeIn($mode, ['show', 'DO_NOT_TRACK=0']);

        self::assertStringContainsString("do-not-track=1\n", $defaultOutput);
        self::assertStringContainsString("do-not-track=unset\n", $trackingOutput);
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

    public function testTheComposeFileNearestTheRootWins(): void
    {
        $directory  = $this->getFixtureCopy(self::COMPOSE_SEARCH_DIRECTORY);
        $filesystem = new Filesystem();
        $found      = [];

        foreach (['docker', 'conf/docker', 'conf', ''] as $composeDirectory) {
            $filesystem->dumpFile(Path::join($directory, $composeDirectory, 'compose.yaml'), "services: {}\n");

            $found[] = $this->runMake(['compose-files'], directory: $directory);
        }

        self::assertSame(
            [
                "compose-files=docker/compose.yaml\n",
                "compose-files=conf/docker/compose.yaml\n",
                "compose-files=conf/compose.yaml\n",
                "compose-files=compose.yaml\n",
            ],
            $found,
        );
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideHostModeCases')]
    public function testAForwardedValueStaysOffTheCommandLine(string $mode): void
    {
        $output = $this->runMakeIn(
            $mode,
            [
                '-n',
                'show',
                'CONTAINER_FIXTURE_UNDECLARED=from-the-command-line',
                'CONTAINER_KEYS=CONTAINER_FIXTURE_TOKEN',
                '--',
                '--format=tsv',
            ],
            ['CONTAINER_FIXTURE_TOKEN' => 'from-the-host'],
        );

        self::assertStringContainsString('-e CONTAINER_FIXTURE_UNDECLARED ', $output);
        self::assertStringContainsString('-e CONTAINER_FIXTURE_TOKEN ', $output);
        self::assertStringNotContainsString('from-the-command-line', $output);
        self::assertStringNotContainsString('from-the-host', $output);
        self::assertStringNotContainsString('-e --format', $output);
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

    public function testAValueFlagAfterTheSeparatorKeepsEveryArgument(): void
    {
        $output = $this->runMakeIn(self::MODE_EXEC, ['arguments', '--', 'tools:call', 'acme', '--tool=user']);

        self::assertSame("tools:call\nacme\n--tool=user\n", $output);
    }

    public function testAToolServiceRunsCommandsInAThrowawayContainerAndOpensNoShell(): void
    {
        $directory  = $this->getFixtureCopy(self::COMPOSE_ENVIRONMENT_DIRECTORY);
        $filesystem = new Filesystem();

        $environment = [
            'APP_SERVICE'     => 'app',
            'DOCKER'          => $directory . '/bin/docker',
            'IS_IN_CONTAINER' => '',
        ];

        $filesystem->dumpFile($directory . '/bin/docker', "#!/bin/sh\nprintf 'services:\\n  app:\\n    working_dir: %s\\n  shellcheck:\\n' \"\$COMPOSE_FIXTURE_DIRECTORY\"\n");
        $filesystem->chmod($directory . '/bin/docker', 0o755);

        $toolExec = $this->runMake(['-n', 'shellcheck-exec', '--', 'shellcheck', '--version'], $environment, $directory);
        $appExec  = $this->runMake(['-n', 'app-exec', '--', 'true'], $environment, $directory);
        $appShell = $this->runMake(['-n', 'app-shell'], $environment, $directory);

        self::assertMatchesRegularExpression('/ run --rm --no-deps .*\bshellcheck \'shellcheck\' \'--version\'$/m', $toolExec);
        self::assertMatchesRegularExpression('/ exec .*\bapp \'true\'$/m', $appExec);
        self::assertMatchesRegularExpression('/ exec .*\bapp +sh$/m', $appShell);

        foreach (['shellcheck-shell', 'shellcheck-logs'] as $target) {
            self::assertStringContainsString(
                'Unknown command',
                $this->runMake([$target], $environment, $directory, doExpectFailure: true),
            );
        }
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

    public function testAContainerCommandEchoesAsTheCommandItRuns(): void
    {
        $wrapped  = $this->runMakeIn(self::MODE_EXEC, ['-n', 'show']);
        $fallback = $this->runMakeIn(self::MODE_EXEC, ['-n', 'show', '.SHELLFLAGS=-c']);

        self::assertMatchesRegularExpression('/^\S+ -c \'\[ -n "\$\(\S+ ps --quiet --filter status=running .+ \|\| \{ .*\S ps --all --quiet .+ \S+ start \$containers .+ \S+ compose -f \S+ up --detach --wait php-app .+; \}; exec "\$@"\' sh \S+ compose -f \S+ exec -e BRNSHKR_EXEC_ID -e (?:\S+ -e )*\S+ php-app sh -c /m', $wrapped);
        self::assertStringContainsString('"$_EXEC_SCRIPT" sh ', $fallback);
    }

    public function testTheProjectsShellFlagsStillApply(): void
    {
        $plain = $this->runMakeIn(self::MODE_EXEC, ['strict']);
        $login = $this->runMakeIn(self::MODE_EXEC, ['--eval=.SHELLFLAGS := -l -c', 'strict']);

        $strict = $this->runMake(
            [
                '--eval=.SHELLFLAGS := -ec',
                'strict',
            ],
            self::getContainerEnvironment(),
            self::CONTAINER_DIRECTORY,
            doExpectFailure: true,
        );

        self::assertStringContainsString('strict=continued', $plain);
        self::assertStringContainsString('strict=continued', $login);
        self::assertStringNotContainsString('strict=continued', $strict);
    }

    /**
     * @param list<string> $shellFlagsArguments
     */
    #[DataProvider('provideAToolInterruptedInsideTheContainerStopsMakeCases')]
    public function testAToolInterruptedInsideTheContainerStopsMake(array $shellFlagsArguments): void
    {
        $process = new Process([
            'setsid',
            '--fork',
            '--wait',
            'make',
            '--no-print-directory',
            '-C',
            self::CONTAINER_DIRECTORY,
            '--keep-going',
            ...$shellFlagsArguments,
            'interrupted',
            'after-interrupted',
        ], env: [
            ...self::getBaselineEnvironment(),
            ...self::getContainerEnvironment(),
        ]);

        $process->run();

        self::assertStringContainsString('Interrupt', $process->getErrorOutput());
        self::assertStringNotContainsString('after-interrupted=ran', $process->getOutput());
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function provideAToolInterruptedInsideTheContainerStopsMakeCases(): iterable
    {
        yield 'shell flags' => [[]];

        yield 'inline fallback' => [['.SHELLFLAGS=-c']];

        yield 'run mode inline fallback' => [['APP_SERVICE_MODE=run', '.SHELLFLAGS=-c']];
    }

    public function testAToolInterruptedUnderTheSpinnerStopsMake(): void
    {
        $process = new Process([
            'setsid',
            '--fork',
            '--wait',
            'make',
            '--no-print-directory',
            '-C',
            self::CONTAINER_DIRECTORY,
            '--keep-going',
            'interrupted-spinner',
            'after-interrupted',
        ], env: [
            ...self::getBaselineEnvironment(),
            ...self::getContainerEnvironment(),
            'NO_COLOR' => '',
        ]);

        $process->setPty(true);
        $process->run();

        self::assertStringContainsString('Running…', $process->getOutput());
        self::assertStringNotContainsString('after-interrupted=ran', $process->getOutput());
    }
}
