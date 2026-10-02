<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

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

    public function testStartupInstallsEachStackThenRunsTheProjectsOwnTargets(): void
    {
        $startupDirectory = $this->getFixtureCopy(self::STARTUP_DIRECTORY);

        $result = $this->runMake(
            ['startup'],
            ['COMPOSER' => 'echo composer', 'BUN' => 'echo bun'],
            directory: $startupDirectory,
        );

        self::assertStringContainsString('composer install', $result);
        self::assertStringContainsString('bun install', $result);
        self::assertStringContainsString("Running startup-own\nstartup-own\n", $result);
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
        self::assertStringContainsString("Running startup-own\nstartup-own\n", $result);
    }
}
