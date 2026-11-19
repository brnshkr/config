<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Filesystem\Filesystem;

use function array_map;
use function array_unique;

/**
 * @internal
 */
#[CoversNothing]
final class DotenvTest extends TestCase
{
    use MakeTrait;
    use MatchesSnapshots;

    private const string DOTENV_DIRECTORY = __DIR__ . '/../Fixtures/Make/Dotenv';

    private const array DOTENV_SCENARIOS = [
        'default'           => [],
        'test-environment'  => ['APP_ENV' => 'test'],
        'local-environment' => ['APP_ENV' => 'local'],
        'environment-wins'  => ['DOTENV_FIXTURE_LAYER' => 'from-the-environment'],
        'disabled'          => ['DOTENV' => '0'],
        'explicit-base'     => ['DOTENV' => 'dist.env'],
        'default-base'      => ['DOTENV' => '1'],
        'unknown-key'       => ['DOTENV_ENV_KEY' => 'FIXTURE_ENV'],
        'ambient-value'     => ['DOTENV_FIXTURE_AMBIENT' => 'from-the-environment'],
        'no-test-envs'      => ['DOTENV_TEST_ENVS' => '', 'APP_ENV' => 'test'],
        'other-default-env' => ['DOTENV_DEFAULT_ENV' => 'test'],
    ];

    public function testAComposeFileReadsWhatOnlyAnEnvironmentFileSets(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/ComposeEnvironment';

        $environment = [
            'DOCKER'          => $directory . '/bin/docker',
            'IS_IN_CONTAINER' => '',
        ];

        $result   = $this->runMake(['app-dir'], $environment, $directory);
        $commands = $this->runMake(['-n', 'ps'], $environment, $directory);

        self::assertStringContainsString('app-dir=/srv/from-environment-file', $result);
        self::assertStringContainsString('compose -f compose.yaml -f compose.override.yaml ps', $commands);
    }

    public function testARefusingComposeIsQuotedRatherThanBlamingTheWorkingDirectory(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/ComposeEnvironment';

        $environment = [
            'DOCKER'          => $directory . '/bin/missing-docker',
            'IS_IN_CONTAINER' => '',
        ];

        $result = $this->runMake(['app-dir'], $environment, $directory, doExpectFailure: true);

        self::assertStringContainsString('The command docker could not be found in this WSL 2 distro.', $result);
    }

    public function testAStageOnTheCommandLineDetectsTheServicesAsItsStageGoalDoes(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/ComposeEnvironment';

        $environment = [
            'DOCKER'          => $directory . '/bin/docker',
            'IS_IN_CONTAINER' => '',
        ];

        $commandLine = $this->runMake(['app-dir', 'APP_ENV=test'], $environment, $directory);

        self::assertStringContainsString('app-dir=/srv/from-environment-file', $commandLine);
        self::assertSame($this->runMake(['test-app-dir'], $environment, $directory), $commandLine);
    }

    public function testDotenvResolution(): void
    {
        $this->assertMatchesSnapshot($this->renderScenarios($this->runMakeConcurrently(array_map(
            static fn (array $environment): array => [
                'args'      => ['dotenv-show'],
                'env'       => $environment,
                'directory' => self::DOTENV_DIRECTORY,
            ],
            self::DOTENV_SCENARIOS,
        ))));
    }

    public function testDotenvRefusesCommandSubstitution(): void
    {
        $result = $this->runMake(
            ['dotenv-show', 'DOTENV=command.env'],
            directory: self::DOTENV_DIRECTORY,
            doExpectFailure: true,
        );

        self::assertStringContainsString('`$(...)`', $result);
        self::assertStringContainsString('use `${VAR}`', $result);
    }

    public function testDotenvReportsAnUnterminatedQuote(): void
    {
        $result = $this->runMake(
            ['dotenv-show', 'DOTENV=unterminated.env'],
            directory: self::DOTENV_DIRECTORY,
            doExpectFailure: true,
        );

        self::assertStringContainsString('unterminated quote', $result);
        self::assertStringContainsString('unterminated.env', $result);
    }

    public function testDotenvRefusesAStageNameMakeWouldReadAsARule(): void
    {
        $dotenvDirectory = $this->getFixtureCopy(self::DOTENV_DIRECTORY);

        new Filesystem()->touch($dotenvDirectory . '/.env.stage:;false;#');

        $result = $this->runMake(['dotenv-show'], directory: $dotenvDirectory, doExpectFailure: true);

        self::assertStringContainsString('`.env.stage:;false;#` does not name a stage', $result);
    }

    public function testAStageGoalRunsNothingWhenTheTargetItNamesIsUnknown(): void
    {
        $known   = $this->runMake(['-n', 'dev-dotenv-show'], directory: self::DOTENV_DIRECTORY);
        $unknown = $this->runMake(['-n', 'dev-nosuchtarget'], directory: self::DOTENV_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('APP_ENV=dev dotenv-show', $known);
        self::assertStringNotContainsString('APP_ENV=dev nosuchtarget', $unknown);
    }

    #[Group('tty')]
    public function testAnAcceptedSuggestionReadsTheStageItNamesAndKeepsDebugOn(): void
    {
        $output = $this->runMakeOnATty(
            ['test-dotenv-shw'],
            self::DOTENV_DIRECTORY,
            "y\n",
            ['NO_COLOR' => '1', 'DEBUG' => '1'],
        );

        self::assertStringContainsString('APP_ENV=test', $output);
        self::assertStringContainsString('| LC_ALL=C sort', $output);
        self::assertStringContainsString('DOTENV_FIXTURE_LAYER=test-env-local', $output);
    }

    public function testEnvListsTheExportsThenWhatEachEnvironmentFileSet(): void
    {
        $outputOfEachForm = array_map(
            fn (array $args): string => $this->runMake(
                ['help', ...$args],
                ['DOTENV_FIXTURE_PLAIN' => 'from the environment'],
                self::DOTENV_DIRECTORY,
            ),
            [['env'], ['e'], ['--', '-e'], ['--', '--env']],
        );

        $plainOutput              = $outputOfEachForm[0];
        $outputWithPrivateExports = $this->runMake(['help', 'env', 'vvv'], directory: self::DOTENV_DIRECTORY);

        $outputWithACommandLineValue = $this->runMake(
            ['help', 'env', 'DOTENV_FIXTURE_LATER=typed'],
            directory: self::DOTENV_DIRECTORY,
        );

        $outputWithEditorLinks = $this->runMake(
            ['help', 'env'],
            ['EDITOR' => 'vscode', 'EDITOR_URL' => 'acme://open/{file}#{line}', ...self::COLORED_ENV],
            self::DOTENV_DIRECTORY,
        );

        [$exportedSection, $environmentFileSection] = Str::split($plainOutput, 'Environment files:', 2) + ['', ''];

        self::assertCount(1, array_unique($outputOfEachForm));
        self::assertMatchesRegularExpression('/^Exported:$/m', $exportedSection);

        self::assertMatchesRegularExpression(
            '/^[\t ]+FIXTURE_MAKEFILE_EXPORT[\t ]+exported by the makefile$/m',
            $exportedSection,
        );

        self::assertStringNotContainsString('DOTENV_FIXTURE_', $exportedSection);
        self::assertStringNotContainsString('PWD', $exportedSection);
        self::assertStringNotContainsString('_HAS_DOCKER', $plainOutput);
        self::assertStringContainsString('_HAS_DOCKER', $outputWithPrivateExports);

        self::assertMatchesRegularExpression(
            '/^[\t ]+\.\/\.env\n[\t ]+DOTENV_FIXTURE_PLAIN[\t ]+one[\t ]+\(replaced by the environment\)$/m',
            $environmentFileSection,
        );

        self::assertMatchesRegularExpression(
            '/^[\t ]+DOTENV_FIXTURE_LAYER[\t ]+base[\t ]+\(replaced by \.\/\.env\.dev\.local\)$/m',
            $environmentFileSection,
        );

        self::assertMatchesRegularExpression(
            '/^[\t ]+\.\/\.env\.dev\.local\n[\t ]+DOTENV_FIXTURE_LAYER[\t ]+dev-local$/m',
            $environmentFileSection,
        );

        self::assertMatchesRegularExpression(
            '/^[\t ]+DOTENV_FIXTURE_LATER[\t ]+later[\t ]+\(replaced by the command line\)$/m',
            $outputWithACommandLineValue,
        );

        self::assertStringContainsString("acme://open/Makefile#13\e\\FIXTURE_MAKEFILE_EXPORT", $outputWithEditorLinks);
        self::assertStringContainsString("acme://open/.env#4\e\\DOTENV_FIXTURE_PLAIN", $outputWithEditorLinks);
    }

    public function testResolveListsAValueOnlyADotenvFileProvides(): void
    {
        $resolved = $this->runMake(['help', 'resolve'], directory: self::DOTENV_DIRECTORY);
        $plain    = $this->runMake(['help'], directory: self::DOTENV_DIRECTORY);

        $linked = $this->runMake(
            ['help', 'resolve'],
            ['EDITOR' => 'vscode', 'EDITOR_URL' => 'acme://open/{file}#{line}', ...self::COLORED_ENV],
            self::DOTENV_DIRECTORY,
        );

        self::assertMatchesRegularExpression('/DOTENV_FIXTURE_LAYER +\?= {2}\.\/\.env\.dev\.local +dev-local\n/', $resolved);
        self::assertStringContainsString("acme://open/.env.dev.local#1\e\\DOTENV_FIXTURE_LAYER", $linked);
        self::assertStringNotContainsString('DOTENV_FIXTURE_LAYER', $plain);
    }

    public function testResolveNamesTheEnvironmentOrCommandLineThatReplacedAValue(): void
    {
        $resolved = $this->runMake(
            [
                'help',
                'resolve',
                'DOTENV_FIXTURE_PLAIN=from-the-command-line',
                'DOTENV_FIXTURE_ONLY_ON_THE_COMMAND_LINE=given',
            ],
            ['DOTENV_FIXTURE_LATER' => 'from-the-environment'],
            self::DOTENV_DIRECTORY,
        );

        self::assertMatchesRegularExpression('/DOTENV_FIXTURE_PLAIN +\?= {2}cli +from-the-command-line\n/', $resolved);
        self::assertMatchesRegularExpression('/DOTENV_FIXTURE_LATER +\?= {2}env +from-the-environment\n/', $resolved);
        self::assertMatchesRegularExpression('/DOTENV_FIXTURE_ONLY_ON_THE_COMMAND_LINE +\?= {2}cli +given\n/', $resolved);
    }

    public function testAStageGoalReadsItsOwnStageWhileTheEnvironmentStillWins(): void
    {
        $stage        = $this->runMake(['test-dotenv-show'], directory: self::DOTENV_DIRECTORY);
        $throughAVerb = $this->runMake(['fixtures', 'FIXTURE_TARGETS=test-dotenv-show'], directory: self::DOTENV_DIRECTORY);

        $overridden = $this->runMake(
            ['test-dotenv-show'],
            ['DOTENV_FIXTURE_LAYER' => 'from-the-environment'],
            self::DOTENV_DIRECTORY,
        );

        self::assertStringContainsString('DOTENV_FIXTURE_ENVIRONMENT_FILE=test', $stage);
        self::assertStringContainsString('DOTENV_FIXTURE_LAYER=test-env-local', $stage);
        self::assertStringContainsString('DOTENV_FIXTURE_ENVIRONMENT_FILE=test', $throughAVerb);
        self::assertStringContainsString('DOTENV_FIXTURE_LAYER=from-the-environment', $overridden);
    }

    public function testEnvAndAStageGoalCallTheConfiguredEnvBinary(): void
    {
        $dotenvDirectory = $this->getFixtureCopy(self::DOTENV_DIRECTORY);
        $filesystem      = new Filesystem();

        $filesystem->dumpFile($dotenvDirectory . '/bin/env', "#!/bin/sh\necho \"acme env with \$# arguments\" >&2\nexec env \"\$@\"\n");
        $filesystem->chmod($dotenvDirectory . '/bin/env', 0o755);

        $exports = $this->runMake(['help', 'env', 'ENV_BIN=bin/env'], directory: $dotenvDirectory);
        $stage   = $this->runMake(['test-dotenv-show', 'ENV_BIN=bin/env'], directory: $dotenvDirectory);

        self::assertStringContainsString('acme env with 0 arguments', $exports);
        self::assertMatchesRegularExpression('/^[\t ]+FIXTURE_MAKEFILE_EXPORT[\t ]+exported by the makefile$/m', $exports);
        self::assertMatchesRegularExpression('/acme env with [1-9]\d* arguments/', $stage);
        self::assertStringContainsString('DOTENV_FIXTURE_LAYER=test-env-local', $stage);
    }

    public function testEveryWayToRunTheTestsRunsThemInTheTestStage(): void
    {
        $testStageDirectory = $this->getFixtureCopy(__DIR__ . '/../Fixtures/Make/TestStage');

        foreach (['test', 'ci', 'phpunit', 'test-phpunit', 'phpunit-update'] as $goal) {
            self::assertStringContainsString(
                'value=from-the-test-file',
                $this->runMake([$goal], directory: $testStageDirectory),
                $goal,
            );
        }
    }
}
