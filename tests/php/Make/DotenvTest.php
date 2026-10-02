<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Filesystem\Filesystem;

use function array_map;
use function array_unique;
use function explode;

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

        $result = $this->runMake(['app-dir'], [
            'DOCKER'          => $directory . '/bin/docker',
            'IS_IN_CONTAINER' => '',
        ], $directory);

        self::assertStringContainsString('app-dir=/srv/from-environment-file', $result);
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

        [$exportedSection, $environmentFileSection] = explode('Environment files:', $plainOutput, 2) + ['', ''];

        self::assertCount(1, array_unique($outputOfEachForm));
        self::assertMatchesRegularExpression('/^Exported:$/m', $exportedSection);

        self::assertMatchesRegularExpression(
            '/^\s+FIXTURE_MAKEFILE_EXPORT\s+exported by the makefile$/m',
            $exportedSection,
        );

        self::assertStringNotContainsString('DOTENV_FIXTURE_', $exportedSection);
        self::assertStringNotContainsString('PWD', $exportedSection);
        self::assertStringNotContainsString('_HAS_DOCKER', $plainOutput);
        self::assertStringContainsString('_HAS_DOCKER', $outputWithPrivateExports);

        self::assertMatchesRegularExpression(
            '/^\s+\.\/\.env\n\s+DOTENV_FIXTURE_PLAIN\s+one\s+\(replaced by the environment\)$/m',
            $environmentFileSection,
        );

        self::assertMatchesRegularExpression(
            '/^\s+DOTENV_FIXTURE_LAYER\s+base\s+\(replaced by \.\/\.env\.dev\.local\)$/m',
            $environmentFileSection,
        );

        self::assertMatchesRegularExpression(
            '/^\s+\.\/\.env\.dev\.local\n\s+DOTENV_FIXTURE_LAYER\s+dev-local$/m',
            $environmentFileSection,
        );

        self::assertMatchesRegularExpression(
            '/^\s+DOTENV_FIXTURE_LATER\s+later\s+\(replaced by the command line\)$/m',
            $outputWithACommandLineValue,
        );

        self::assertStringContainsString("acme://open/Makefile#13\e\\FIXTURE_MAKEFILE_EXPORT", $outputWithEditorLinks);
        self::assertStringContainsString("acme://open/.env#4\e\\DOTENV_FIXTURE_PLAIN", $outputWithEditorLinks);
    }

    public function testResolveListsAValueOnlyADotenvFileProvides(): void
    {
        $resolved = $this->runMake(['help', 'resolve'], directory: self::DOTENV_DIRECTORY);
        $plain    = $this->runMake(['help'], directory: self::DOTENV_DIRECTORY);

        self::assertStringContainsString('DOTENV_FIXTURE_LAYER', $resolved);
        self::assertStringContainsString('dev-local', $resolved);
        self::assertStringNotContainsString('DOTENV_FIXTURE_LAYER', $plain);
    }

    public function testAStageGoalReadsItsOwnStageWhileTheEnvironmentStillWins(): void
    {
        $stage = $this->runMake(['test-dotenv-show'], directory: self::DOTENV_DIRECTORY);

        $overridden = $this->runMake(
            ['test-dotenv-show'],
            ['DOTENV_FIXTURE_LAYER' => 'from-the-environment'],
            self::DOTENV_DIRECTORY,
        );

        self::assertStringContainsString('DOTENV_FIXTURE_ENVIRONMENT_FILE=test', $stage);
        self::assertStringContainsString('DOTENV_FIXTURE_LAYER=test-env-local', $stage);
        self::assertStringContainsString('DOTENV_FIXTURE_LAYER=from-the-environment', $overridden);
    }
}
