<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make\Trait;

use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

use function array_unique;
use function basename;
use function is_readable;
use function posix_getgid;
use function posix_getuid;

/**
 * @internal
 *
 * @phpstan-require-extends TestCase
 */
trait ContainerTrait
{
    use MakeTrait;

    private const string CONTAINER_DIRECTORY = __DIR__ . '/../../Fixtures/Make/Container';
    private const string PROJECT_NAME        = 'brnshkr-config-container-test';
    private const string MOUNT_ROOT          = '/app';

    private const string MODE_NATIVE = 'native';
    private const string MODE_EXEC   = 'host, exec';
    private const string MODE_RUN    = 'host, run --rm';
    private const string MODE_INSIDE = 'inside the container';

    private const array CONTAINER_MODES = [
        self::MODE_EXEC,
        self::MODE_RUN,
        self::MODE_INSIDE,
    ];

    /**
     * @var list<string>
     */
    private static array $startedFixtureDirectories = [];

    #[AfterClass]
    public static function removeWhatTheContainerFixturesStarted(): void
    {
        foreach (array_unique(self::$startedFixtureDirectories) as $fixtureDirectory) {
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

        self::$startedFixtureDirectories = [];
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

        if ($mode !== self::MODE_NATIVE) {
            self::$startedFixtureDirectories[] = $fixtureDirectory;
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
        return self::MOUNT_ROOT . '/' . self::getProjectRelativePath($fixtureDirectory);
    }

    private static function getHostUserAndGroup(): string
    {
        return posix_getuid() . ':' . posix_getgid();
    }

    private static function getImage(): string
    {
        return Str::fromEnvironment('FORGE_IMAGE')
            ?: throw new RuntimeException('`FORGE_IMAGE` is unset, run through make.');
    }
}
