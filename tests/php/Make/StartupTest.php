<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

use function array_map;

/**
 * @internal
 */
#[CoversNothing]
final class StartupTest extends TestCase
{
    use MakeTrait;

    private const string STARTUP_DIRECTORY = __DIR__ . '/../Fixtures/Make/Startup';
    private const string STARTER_DIRECTORY = __DIR__ . '/../Fixtures/Make/Starter';
    private const string TRUST_DIRECTORY   = __DIR__ . '/../Fixtures/Make/Trust';

    public function testStartupInstallsEachStackThenRunsTheProjectsOwnTargets(): void
    {
        $startupDirectory = $this->getFixtureCopy(self::STARTUP_DIRECTORY);

        $result = $this->runMake(
            ['startup'],
            ['BUN' => 'echo bun', 'CI' => '', 'COMPOSER' => 'echo composer'],
            directory: $startupDirectory,
        );

        self::assertStringContainsString('composer install --no-progress', $result);
        self::assertStringContainsString('bun install', $result);
        self::assertStringNotContainsString('composer validate', $result);
        self::assertStringNotContainsString('--frozen-lockfile', $result);
        self::assertStringContainsString("Running `startup-own`\nstartup-own\n", $result);
    }

    public function testStartupRefusesAStaleLockUnderCi(): void
    {
        $startupDirectory = $this->getFixtureCopy(self::STARTUP_DIRECTORY);

        $result = $this->runMake(
            ['startup'],
            ['BUN' => 'echo bun', 'CI' => 'true', 'COMPOSER' => 'echo composer'],
            directory: $startupDirectory,
        );

        self::assertStringContainsString('composer validate --no-check-all --no-check-publish --no-interaction', $result);
        self::assertStringContainsString('bun install --frozen-lockfile', $result);
    }

    public function testTheStarterInstallsAgainstTheBunLockUnderCi(): void
    {
        $starterDirectory = $this->getFixtureCopy(self::STARTER_DIRECTORY);
        $filesystem       = new Filesystem();

        $filesystem->rename($starterDirectory . '/yarn.lock', $starterDirectory . '/bun.lock');
        $filesystem->dumpFile($starterDirectory . '/bin/bun', "#!/bin/sh\necho \"bun $*\"\n");
        $filesystem->chmod($starterDirectory . '/bin/bun', 0o755);

        $result = $this->runMake(
            ['JS_SERVICE='],
            ['CI' => 'true', 'PATH' => $starterDirectory . '/bin:' . Str::fromEnvironment('PATH')],
            $starterDirectory,
            doExpectFailure: true,
        );

        self::assertStringContainsString('bun install --frozen-lockfile', $result);
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

    public function testTrustInstallsTheCertificateAuthorityWhereThisMachineKeepsItsRoots(): void
    {
        [$windows, $linux, $unknown] = array_map(
            fn (string $binDirectory): string => $this->runMake(
                ['trust'],
                [
                    'HOME' => self::TRUST_DIRECTORY,
                    'PATH' => self::TRUST_DIRECTORY . $binDirectory . ':/usr/bin:/bin',
                ],
                self::TRUST_DIRECTORY,
                doExpectFailure: $binDirectory === '',
            ),
            ['/bin-wsl', '/bin-linux', ''],
        );

        self::assertMatchesRegularExpression('#certutil\.exe -addstore -user Root wslpath -w \S+/Trust/\.cache/xdg\.cache/caddy/#', $windows);
        self::assertMatchesRegularExpression('#sudo cp \S+/root\.crt /usr/local/share/ca-certificates/Trust\.crt#', $linux);
        self::assertStringContainsString('sudo update-ca-certificates', $linux);
        self::assertStringContainsString('No trust store found', $unknown);
    }

    public function testTheStarterHandsComposeTheEnvironmentFilesResolvedAsAWhole(): void
    {
        $environment = ['PATH' => self::STARTER_DIRECTORY . '/bin:' . Str::fromEnvironment('PATH')];
        $result      = $this->runMake([], $environment, self::STARTER_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('ACME_IMAGE=acme/user:mine ACME_TITLE=Acme Shop', $result);
    }
}
