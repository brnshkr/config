<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class StartupTest extends TestCase
{
    use MakeTrait;

    private const string STARTUP_DIRECTORY = __DIR__ . '/../Fixtures/Make/Startup';
    private const string STARTER_DIRECTORY = __DIR__ . '/../Fixtures/Make/Starter';

    public function testStartupInstallsEachStackThenRunsTheProjectsOwnTargets(): void
    {
        $startupDirectory = $this->getFixtureCopy(self::STARTUP_DIRECTORY);

        $result = $this->runMake(
            ['startup'],
            ['COMPOSER' => 'echo composer', 'BUN' => 'echo bun'],
            directory: $startupDirectory,
        );

        self::assertStringContainsString('composer install --no-progress', $result);
        self::assertStringContainsString('bun install', $result);
        self::assertStringContainsString("Running `startup-own`\nstartup-own\n", $result);
    }

    public function testStartupSkipsTheInstallsTheCallerAlreadyRan(): void
    {
        $startupDirectory = $this->getFixtureCopy(self::STARTUP_DIRECTORY);

        $result = $this->runMake(
            ['startup', '_IS_INSTALLING=1'],
            ['COMPOSER' => 'echo composer', 'BUN' => 'echo bun'],
            directory: $startupDirectory,
        );

        self::assertStringNotContainsString('composer install', $result);
        self::assertStringNotContainsString('bun install', $result);
        self::assertStringContainsString("Running `startup-own`\nstartup-own\n", $result);
    }

    public function testTheStarterInstallsJavaScriptWhereJsServiceSays(): void
    {
        $environment   = ['PATH' => self::STARTER_DIRECTORY . '/bin:' . Str::fromEnvironment('PATH')];
        $inService     = $this->runMake([], $environment, self::STARTER_DIRECTORY, doExpectFailure: true);
        $onThisMachine = $this->runMake(['JS_SERVICE='], $environment, self::STARTER_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString(' run --rm app yarn install', $inService);
        self::assertStringContainsString('yarn install', $onThisMachine);
        self::assertStringNotContainsString('docker', $onThisMachine);
    }

    public function testTheStarterHandsComposeTheEnvironmentFilesResolvedAsAWhole(): void
    {
        $environment = ['PATH' => self::STARTER_DIRECTORY . '/bin:' . Str::fromEnvironment('PATH')];
        $result      = $this->runMake([], $environment, self::STARTER_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('ACME_IMAGE=acme/user:mine ACME_TITLE=Acme Shop', $result);
    }
}
