<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Json;
use JsonException;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\DependencyInjection\LoaderFactory;
use PHPStan\File\FileHelper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

use function getcwd;

/**
 * @internal
 */
#[CoversNothing]
final class PhpstanTest extends TestCase
{
    use MatchesSnapshots;

    /**
     * @throws JsonException
     */
    public function testExpectedPhpstanConfig(): void
    {
        $currentWorkingDirectory = getcwd() ?: '.';
        $containerFactory        = new ContainerFactory($currentWorkingDirectory);

        // @phpstan-ignore phpstanApi.constructor, phpstanApi.method (Not covered by BC)
        $loader = new LoaderFactory(
            // @phpstan-ignore phpstanApi.constructor (Not covered by BC)
            fileHelper: new FileHelper($currentWorkingDirectory),
            rootDir: $containerFactory->getRootDirectory(),
            currentWorkingDirectory: $containerFactory->getCurrentWorkingDirectory(),
            generateBaselineFile: null,
            expandRelativePaths: [],
        )->createLoader();

        $config = $loader->load($currentWorkingDirectory . '/conf/phpstan.neon', null);
        $result = Json::encode($config);

        $this->assertMatchesJsonSnapshot($result);
    }
}
