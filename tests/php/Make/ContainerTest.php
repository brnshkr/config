<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

use function basename;
use function getenv;
use function is_link;
use function is_readable;
use function md5_file;
use function posix_getgid;
use function posix_getuid;
use function readlink;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal
 */
#[CoversNothing]
#[Group('container')]
final class ContainerTest extends TestCase
{
    use MakeTrait;

    private const string CONTAINER_DIRECTORY = __DIR__ . '/../Fixtures/Make/Container';
    private const string PROJECT_DIRECTORY   = __DIR__ . '/../../..';

    private const string PROJECT_NAME  = 'brnshkr-config-container-test';
    private const string MOUNT_ROOT    = '/app';
    private const string SERVICE       = 'app';
    private const string OTHER_SERVICE = 'alt';

    private const string MODE_NATIVE = 'native';
    private const string MODE_EXEC   = 'host, exec';
    private const string MODE_RUN    = 'host, run --rm';
    private const string MODE_INSIDE = 'inside the container';

    private const array CONTAINER_MODES = [
        self::MODE_EXEC,
        self::MODE_RUN,
        self::MODE_INSIDE,
    ];

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

    #[AfterClass]
    public static function removeWhatTheContainerFixturesStarted(): void
    {
        foreach ([self::CONTAINER_DIRECTORY, self::CONFIGS_DIRECTORY, self::TSCONFIG_DIRECTORY] as $fixtureDirectory) {
            $environment = self::getContainerEnvironment($fixtureDirectory);

            $projectNames = [
                $environment['COMPOSE_PROJECT_NAME'],
                Str::toLowerCase(basename($fixtureDirectory)),
            ];

            foreach ($projectNames as $projectName) {
                new Process([
                    'docker',
                    'compose',
                    '--project-name',
                    $projectName,
                    'down',
                    '--timeout',
                    '1',
                ], cwd: $fixtureDirectory, env: [
                    ...$environment,
                    'HOST_UID' => (string) posix_getuid(),
                    'HOST_GID' => (string) posix_getgid(),
                ])->run();
            }
        }
    }

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
    public function testAToolSeesTheDirectoryTheProjectIsMountedAt(string $mode): void
    {
        $output = $this->runMakeIn($mode, ['show']);

        $mountPath = $mode === self::MODE_NATIVE
            ? self::getRealPath(self::CONTAINER_DIRECTORY)
            : self::getContainerPath();

        self::assertStringContainsString('where=' . $mountPath, $output);
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
     * @return iterable<string, array{self::MODE_*}>
     */
    public static function provideHostModeCases(): iterable
    {
        foreach ([self::MODE_EXEC, self::MODE_RUN] as $mode) {
            yield $mode => [$mode];
        }
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
        $docker = $this->runMakeIn(self::MODE_INSIDE, ['detect']);
        $host   = $this->runMakeIn(self::MODE_NATIVE, ['detect']);

        $podman = $this->runInContainer(
            ['sh', '-c', 'rm /.dockerenv && touch /run/.containerenv && make --no-print-directory detect'],
            userAndGroup: '0:0',
        );

        self::assertStringContainsString('in-container=1', $docker);
        self::assertStringContainsString('in-container=1', $podman);
        self::assertStringNotContainsString('in-container=1', $host);
    }

    public function testAnExplicitSettingOverridesTheDetection(): void
    {
        $inside = $this->runInContainer(['make', '--no-print-directory', 'detect', 'IS_IN_CONTAINER=']);

        self::assertStringNotContainsString('in-container=1', $inside);
    }

    public function testATargetPerServiceRunsInThatService(): void
    {
        $service = $this->runMake(
            [self::SERVICE . '-exec', '--', 'pwd'],
            self::getContainerEnvironment(),
            self::CONTAINER_DIRECTORY,
        );

        $other = $this->runMake(
            [self::OTHER_SERVICE . '-exec', '--', 'pwd'],
            self::getContainerEnvironment(),
            self::CONTAINER_DIRECTORY,
        );

        self::assertStringContainsString(self::getContainerPath(), $service);
        self::assertStringContainsString(self::getContainerPath(), $other);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideContainerModeCases')]
    public function testConfigsWritesTheSameFilesAsANativeRun(string $mode): void
    {
        $this->assertWritesTheSameFilesAsANativeRun($mode, self::CONFIGS_DIRECTORY, ['configs', 'local']);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideContainerModeCases')]
    public function testTheTypescriptProjectIsLinkedTheSameAsANativeRun(string $mode): void
    {
        $this->assertWritesTheSameFilesAsANativeRun($mode, self::TSCONFIG_DIRECTORY, ['configs']);
    }

    /**
     * @return iterable<string, array{self::MODE_*}>
     */
    public static function provideContainerModeCases(): iterable
    {
        foreach (self::CONTAINER_MODES as $mode) {
            yield $mode => [$mode];
        }
    }

    /**
     * @param self::MODE_* $mode
     * @param list<string> $makeArguments
     */
    private function assertWritesTheSameFilesAsANativeRun(
        string $mode,
        string $fixtureDirectory,
        array $makeArguments,
    ): void {
        $nativeOutput = $this->runMakeIn(self::MODE_NATIVE, $makeArguments, fixtureDirectory: $fixtureDirectory);
        $nativeFiles  = self::describeFiles($fixtureDirectory);

        $this->removeWhatTheFixturesWrote();

        $modeOutput = $this->runMakeIn($mode, $makeArguments, fixtureDirectory: $fixtureDirectory);

        self::assertSame($nativeFiles, self::describeFiles($fixtureDirectory));
        self::assertSame($nativeOutput, $modeOutput);
    }

    /**
     * @param self::MODE_* $mode
     * @param list<string> $makeArguments
     * @param array<string, string> $environment
     */
    private function runMakeIn(
        string $mode,
        array $makeArguments,
        array $environment = [],
        string $fixtureDirectory = self::CONTAINER_DIRECTORY,
    ): string {
        $modeEnvironment = match ($mode) {
            self::MODE_NATIVE => ['APP_SERVICE' => ''],
            self::MODE_EXEC   => [],
            self::MODE_RUN    => ['APP_SERVICE_MODE' => 'run'],
            self::MODE_INSIDE => [],
        };

        if ($mode === self::MODE_INSIDE) {
            return $this->runInContainer(
                ['make', '--no-print-directory', ...$makeArguments],
                $environment,
                fixtureDirectory: $fixtureDirectory,
            );
        }

        return $this->runMake(
            $makeArguments,
            [
                ...self::getContainerEnvironment($fixtureDirectory),
                ...$modeEnvironment,
                ...$environment,
            ],
            $fixtureDirectory,
        );
    }

    /**
     * @param list<string> $command
     * @param array<string, string> $environment
     */
    private function runInContainer(
        array $command,
        array $environment = [],
        ?string $userAndGroup = null,
        string $fixtureDirectory = self::CONTAINER_DIRECTORY,
    ): string {
        $environmentFlags = [];

        foreach ([...self::BASELINE_ENV, ...$environment] as $name => $value) {
            $environmentFlags = [...$environmentFlags, '-e', $name . '=' . $value];
        }

        $process = new Process([
            'docker',
            'run',
            '--rm',
            '-u',
            $userAndGroup ?? self::getHostUserAndGroup(),
            '-v',
            self::getRealPath(self::PROJECT_DIRECTORY) . ':' . self::MOUNT_ROOT,
            '-w',
            self::getContainerPath($fixtureDirectory),
            ...$environmentFlags,
            self::getImage(),
            ...$command,
        ], env: self::getBaselineEnvironment());

        $process->run();

        return $this->normalizeOutput($process->getOutput() . $process->getErrorOutput());
    }

    /**
     * @return list<string>
     */
    private static function describeFiles(string $fixtureDirectory): array
    {
        $descriptions = [];

        $finder = new Finder()
            ->in($fixtureDirectory)
            ->files()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->sortByName()
        ;

        foreach ($finder as $entry) {
            $entryPath = $entry->getPathname();

            if (is_link($entryPath)) {
                $descriptions[] = $entry->getRelativePathname() . ': link to ' . readlink($entryPath);

                continue;
            }

            if (!is_readable($entryPath)) {
                self::fail(sprintf('`%s` was written but cannot be read.', $entry->getRelativePathname()));
            }

            $descriptions[] = $entry->getRelativePathname() . ': ' . md5_file($entryPath);
        }

        return $descriptions;
    }

    /**
     * @return array{
     *     COMPOSE_PROJECT_NAME: string,
     *     FORGE_IMAGE: string,
     *     COMPOSE_FILE?: string,
     * }
     */
    private static function getContainerEnvironment(string $fixtureDirectory = self::CONTAINER_DIRECTORY): array
    {
        $ownComposeFile = self::getRealPath($fixtureDirectory) . '/container.compose.yaml';

        return [
            'COMPOSE_PROJECT_NAME' => self::PROJECT_NAME . '-' . Str::toLowerCase(basename($fixtureDirectory)),
            'FORGE_IMAGE'          => self::getImage(),
            ...(is_readable($ownComposeFile) ? ['COMPOSE_FILE' => $ownComposeFile] : []),
        ];
    }

    private static function getContainerPath(string $fixtureDirectory = self::CONTAINER_DIRECTORY): string
    {
        return s(self::getRealPath($fixtureDirectory))
            ->after(self::getRealPath(self::PROJECT_DIRECTORY))
            ->prepend(self::MOUNT_ROOT)
            ->toString()
        ;
    }

    private static function getHostUserAndGroup(): string
    {
        return posix_getuid() . ':' . posix_getgid();
    }

    private static function getImage(): string
    {
        return getenv('FORGE_IMAGE') ?: throw new RuntimeException('`FORGE_IMAGE` is unset, run through make.');
    }
}
