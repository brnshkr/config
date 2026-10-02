<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Tests\Make\Trait\ContainerTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function sprintf;

/**
 * @internal
 */
#[CoversNothing]
#[Group('container')]
final class ContainerTest extends TestCase
{
    use ContainerTrait;

    private const string SERVICE       = 'app';
    private const string OTHER_SERVICE = 'alt';

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
        $service = $this->runMakeIn(self::MODE_EXEC, [self::SERVICE . '-exec', '--', 'pwd']);
        $other   = $this->runMakeIn(self::MODE_EXEC, [self::OTHER_SERVICE . '-exec', '--', 'pwd']);

        self::assertStringContainsString(self::getContainerPath(), $service);
        self::assertStringContainsString(self::getContainerPath(), $other);
    }

    /**
     * @param self::MODE_* $mode
     */
    #[DataProvider('provideHostModeCases')]
    public function testTheAppServiceTargetsEnterItTheWayTheModeSays(string $mode): void
    {
        $shell = $this->runMakeIn($mode, ['shell', '--', 'printenv', 'COLUMNS'], ['COLUMNS' => '123']);
        $exec  = $this->runMakeIn($mode, ['exec', '--', 'pwd']);

        self::assertStringContainsString('123', $shell);
        self::assertStringContainsString(self::getContainerPath(), $exec);
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
