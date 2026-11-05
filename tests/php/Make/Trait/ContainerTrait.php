<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make\Trait;

use Brnshkr\Config\Str;
use PHPUnit\Framework\TestCase;

use function basename;
use function is_readable;

/**
 * @internal
 *
 * @phpstan-require-extends TestCase
 */
trait ContainerTrait
{
    use MakeTrait;

    protected const array CONTAINER_MODES = [
        self::MODE_EXEC,
        self::MODE_RUN,
        self::MODE_INSIDE,
    ];

    private const string CONTAINER_DIRECTORY = __DIR__ . '/../../Fixtures/Make/Container';
    private const string PROJECT_NAME        = 'brnshkr-config-container-test';
    private const string MOUNT_ROOT          = '/app';
    private const string FORGE_IMAGE         = 'brnshkr/forge:dev';
    private const string SEMGREP_IMAGE       = 'semgrep/semgrep:1.0.0-nonroot';

    private const string MODE_NATIVE = 'native';
    private const string MODE_EXEC   = 'host, exec';
    private const string MODE_RUN    = 'host, run --rm';
    private const string MODE_INSIDE = 'inside the container';

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
            self::MODE_INSIDE => ['IS_IN_CONTAINER' => '1'],
        };

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
     * @return array{
     *     COMPOSE_PROJECT_NAME: string,
     *     DOCKER: string,
     *     FORGE_IMAGE: string,
     *     IS_IN_CONTAINER: string,
     *     SEMGREP_IMAGE: string,
     *     COMPOSE_FILE?: string,
     * }
     */
    private static function getContainerEnvironment(string $fixtureDirectory = self::CONTAINER_DIRECTORY): array
    {
        $ownComposeFile = self::getRealPath($fixtureDirectory) . '/container.compose.yaml';

        return [
            'COMPOSE_PROJECT_NAME' => self::PROJECT_NAME . '-' . Str::toLowerCase(basename($fixtureDirectory)),
            'DOCKER'               => self::getRealPath(self::CONTAINER_DIRECTORY) . '/bin/docker',
            'FORGE_IMAGE'          => self::FORGE_IMAGE,
            'IS_IN_CONTAINER'      => '',
            'SEMGREP_IMAGE'        => self::SEMGREP_IMAGE,
            ...(is_readable($ownComposeFile) ? ['COMPOSE_FILE' => $ownComposeFile] : []),
        ];
    }

    private static function getContainerPath(string $fixtureDirectory = self::CONTAINER_DIRECTORY): string
    {
        return self::MOUNT_ROOT . '/' . self::getProjectRelativePath($fixtureDirectory);
    }
}
