<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\ContainerTrait;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function array_map;
use function array_values;
use function time;

/**
 * @internal
 */
#[CoversNothing]
final class ToolsTest extends TestCase
{
    use ContainerTrait;
    use MatchesSnapshots;

    private const string LINTERS_DIRECTORY       = __DIR__ . '/../Fixtures/Make/Linters';
    private const string COVERAGE_DIRECTORY      = __DIR__ . '/../Fixtures/Make/Coverage';
    private const string SETTINGS_DIRECTORY      = __DIR__ . '/../Fixtures/Make/Settings';
    private const string COMPOSER_DIRECTORY      = __DIR__ . '/../Fixtures/Make/ComposerDirectories';
    private const string GITHUB_EVENTS_DIRECTORY = __DIR__ . '/../Fixtures/Make/GitHubEvents';
    private const string DEPLOY_DIRECTORY        = __DIR__ . '/../Fixtures/Make/Deploy';

    #[Before]
    #[After]
    public function removeWhatTheContainerRunsWrote(): void
    {
        new Filesystem()->remove(self::LINTERS_DIRECTORY . '/.cache');
    }

    public function testAComposerToolIsFoundInTheBinDirectoryComposerJsonNames(): void
    {
        $resolved = $this->runMake(['help', 'resolve', 'vv'], ['VALUE_WIDTH' => '200'], self::COMPOSER_DIRECTORY);

        self::assertMatchesRegularExpression('/PHP_STAN\s+\?=\s+\S*\/tools\/bin\/phpstan\s/', $resolved);
    }

    public function testEveryBunToolRunsItsBinaryRatherThanAScriptOfTheSameName(): void
    {
        $help = $this->runMake(['help', 'resolve', 'v'], directory: __DIR__ . '/../Fixtures/Make/Bun');

        foreach (['commitlint', 'eslint', 'markdownlint-cli2', 'stylelint', 'tsc', 'vitest'] as $binary) {
            self::assertStringContainsString('bun --bun x ' . $binary . ' ', $help);
        }
    }

    public function testAnInspectorIsOfferedOnlyWhereItIsInstalled(): void
    {
        $installed = $this->runMake(['help', 'vv'], directory: __DIR__ . '/../Fixtures/Make/Bun');
        $missing   = $this->runMake(['help', 'vv'], directory: __DIR__ . '/../Fixtures/Make/Verbs');

        self::assertStringContainsString('eslint-inspect', $installed);
        self::assertStringContainsString('bun-inspect', $installed);
        self::assertStringNotContainsString('eslint-inspect', $missing);
        self::assertStringNotContainsString('bun-inspect', $missing);
    }

    public function testEverySourceAProjectPinsAFloorInReachesTheRun(): void
    {
        $run = $this->runMake(
            ['-n', 'phpunit-coverage', 'PHP_UNIT_MIN_COVERAGE_LINES=44'],
            ['CI' => '1'],
            self::SETTINGS_DIRECTORY,
        );

        self::assertStringContainsString('_METRIC=\'Classes\' -v _MINIMUM=\'42\'', $run);
        self::assertStringContainsString('_METRIC=\'Methods\' -v _MINIMUM=\'43\'', $run);
        self::assertStringContainsString('_METRIC=\'Lines\' -v _MINIMUM=\'44\'', $run);
    }

    public function testCoverageIsReadFromTheSummaryRatherThanTheLastLineThatMentionsLines(): void
    {
        $reached = $this->runMake(['coverage-reached'], directory: self::COVERAGE_DIRECTORY);
        $missed  = $this->runMake(['coverage-missed'], directory: self::COVERAGE_DIRECTORY, doExpectFailure: true);
        $absent  = $this->runMake(['coverage-absent'], directory: self::COVERAGE_DIRECTORY, doExpectFailure: true);
        $metric  = $this->runMake(['coverage-metric'], directory: self::COVERAGE_DIRECTORY, doExpectFailure: true);

        self::assertStringNotContainsString('coverage is below', $reached);
        self::assertStringContainsString('coverage is below', $missed);
        self::assertStringContainsString('100%', $missed);
        self::assertStringContainsString('`none.txt` is missing', $absent);
        self::assertStringNotContainsString('coverage is below', $absent);
        self::assertStringContainsString('Methods', $metric);
        self::assertStringContainsString('95%', $metric);
    }

    public function testTheRectorRulesInUseAreListed(): void
    {
        $result = $this->runMake(['rector-print'], directory: self::PROJECT_DIRECTORY);

        self::assertStringStartsWith(
            "Loaded rector rules\n===================\n\n * Brnshkr\\Config\\Rector\\Rule\\FencedCodeRector\n * Rector\\",
            $result,
        );

        self::assertStringContainsString(' * Rector\DeadCode\Rector\\', $result);
        self::assertMatchesRegularExpression('/\nLoaded \d+ rules\n$/', $result);
    }

    public function testTheTwigCsFixerRulesInUseAreListed(): void
    {
        $result = $this->runMake(['twig-cs-fixer-print'], directory: self::PROJECT_DIRECTORY);

        self::assertStringStartsWith(
            "Loaded twig-cs-fixer rules\n==========================\n\n * Brnshkr\\Config\\TwigCsFixer\\Rule\\ProperNameRule\n * TwigCsFixer\\",
            $result,
        );

        self::assertMatchesRegularExpression('/\nLoaded \d+ rules\n$/', $result);
    }

    public function testComposerRunsACommandThatTakesNoInstallOptions(): void
    {
        $result = $this->runMake(
            ['composer', '--', 'show', '--self', '--name-only'],
            directory: self::PROJECT_DIRECTORY,
        );

        self::assertSame("brnshkr/config\n", $result);
    }

    public function testEveryPestTargetRefusesWhenPestIsNotInstalled(): void
    {
        foreach (['pest', 'pest-debug', 'pest-list', 'pest-update', 'pest-coverage'] as $target) {
            $result = $this->runMake(
                [$target],
                directory: __DIR__ . '/../Fixtures/Make/Guard',
                doExpectFailure: true,
            );

            self::assertStringContainsString('`pest` is not installed', $result, $target);
        }
    }

    public function testHadolintResolvesItsDockerfiles(): void
    {
        $this->assertMatchesSnapshot($this->runLinter(['hadolint-list']));
    }

    public function testShellcheckResolvesItsScriptsAndCodeBlocks(): void
    {
        $this->assertMatchesSnapshot($this->runLinter(['shellcheck-list']));
    }

    public function testAShellCodeBlockKeepsTheLinesOfItsPage(): void
    {
        $this->runLinter(['shellcheck-list']);

        $codeBlocksDirectory = $this->getFixtureCopy(self::LINTERS_DIRECTORY) . '/.cache/shellcheck.cache/docs/usage.md';

        $this->assertMatchesSnapshot(new Filesystem()->readFile($codeBlocksDirectory . '/4.sh'));
        $this->assertMatchesSnapshot(new Filesystem()->readFile($codeBlocksDirectory . '/16.sh'));
        self::assertFileDoesNotExist($codeBlocksDirectory . '/10.sh');
        self::assertFileDoesNotExist($codeBlocksDirectory . '/21.sh');
    }

    public function testAShellcheckFindingNamesThePageOfItsCodeBlock(): void
    {
        $this->assertMatchesSnapshot(Str::replaceMatches(
            $this->runLinter(['shellcheck', 'SHELLCHECK_FIXTURE_FINDINGS=1'], doExpectFailure: true),
            '/Makefile:\d+/',
            static fn (): string => 'Makefile:<line>',
        ));
    }

    public function testALintSkipsADeletedTrackedFile(): void
    {
        $lintersDirectory = $this->getFixtureCopy(self::LINTERS_DIRECTORY);

        new Filesystem()->remove($lintersDirectory . '/docker/app.Dockerfile');

        $result = $this->runMake(
            ['hadolint-list'],
            ['PATH' => $lintersDirectory . '/bin:' . Str::fromEnvironment('PATH')],
            $lintersDirectory,
        );

        self::assertStringNotContainsString('docker/app.Dockerfile', $result);
        self::assertMatchesRegularExpression('/^Dockerfile$/m', $result);
        self::assertStringContainsString('docker/it\'s dé jà.Dockerfile', $result);
    }

    public function testALintTakesAnUntrackedFileGitKeeps(): void
    {
        $lintersDirectory = $this->getFixtureCopy(self::LINTERS_DIRECTORY);
        $filesystem       = new Filesystem();

        $filesystem->dumpFile($lintersDirectory . '/.gitignore', "/docker/ignored.Dockerfile\n");
        $filesystem->dumpFile($lintersDirectory . '/docker/ignored.Dockerfile', '');
        $filesystem->dumpFile($lintersDirectory . '/docker/untracked.Dockerfile', '');

        $result = $this->runMake(
            ['hadolint-list'],
            ['PATH' => $lintersDirectory . '/bin:' . Str::fromEnvironment('PATH')],
            $lintersDirectory,
        );

        self::assertStringContainsString('docker/untracked.Dockerfile', $result);
        self::assertStringNotContainsString('docker/ignored.Dockerfile', $result);
    }

    public function testActionlintResolvesItsWorkflows(): void
    {
        $this->assertMatchesSnapshot($this->runLinter(['actionlint-list']));
    }

    /**
     * @param array<string, string> $environment
     */
    #[DataProvider('provideCommitlintReadsTheRangeTheCiNamesCases')]
    public function testCommitlintReadsTheRangeTheCiNames(array $environment, string $expectedSource): void
    {
        $result = $this->runMake(['-n', 'commitlint'], [
            'CI_COMMIT_BEFORE_SHA'           => '',
            'CI_MERGE_REQUEST_DIFF_BASE_SHA' => '',
            'GITHUB_BASE_REF'                => '',
            'GITHUB_EVENT_PATH'              => '',
            ...$environment,
        ], self::TOOLS_DIRECTORY);

        self::assertStringContainsString($expectedSource, $result);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function provideCommitlintReadsTheRangeTheCiNamesCases(): iterable
    {
        $commit      = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';
        $emptyCommit = '0000000000000000000000000000000000000000';
        $commitRange = '--from ' . $commit . ' --to HEAD';

        yield 'GitHub pull request' => [['GITHUB_BASE_REF' => 'master'], '--from origin/master --to HEAD'];

        yield 'GitHub push' => [['GITHUB_EVENT_PATH' => self::GITHUB_EVENTS_DIRECTORY . '/push.json'], $commitRange];

        yield 'GitHub new branch' => [['GITHUB_EVENT_PATH' => self::GITHUB_EVENTS_DIRECTORY . '/new-branch.json'], '--last'];

        yield 'GitLab merge request' => [['CI_MERGE_REQUEST_DIFF_BASE_SHA' => $commit], $commitRange];

        yield 'GitLab push' => [['CI_COMMIT_BEFORE_SHA' => $commit], $commitRange];

        yield 'GitLab new branch' => [['CI_COMMIT_BEFORE_SHA' => $emptyCommit], '--last'];

        yield 'a range set by hand' => [
            [
                'COMMITLINT_RANGE' => 'origin/dev..HEAD',
                'GITHUB_BASE_REF'  => 'master',
            ],
            '--from origin/dev --to HEAD',
        ];
    }

    public function testAContainerOffersALinterOnlyWhereItsServiceOrTheImageCarriesIt(): void
    {
        $lintersDirectory = $this->getFixtureCopy(self::LINTERS_DIRECTORY);
        $listings         = [];

        foreach ([
            'other image' => ['COMPOSE_FIXTURE_IMAGE' => 'acme/app:1'],
            'forge image' => ['COMPOSE_FIXTURE_IMAGE' => 'docker.io/brnshkr/forge-php:1.0.0-cli'],
            'forge app'   => ['COMPOSE_FIXTURE_IMAGE' => 'docker.io/brnshkr/forge-php:1.0.0-app-dev'],
            'local forge' => ['COMPOSE_FIXTURE_IMAGE' => 'localhost/brnshkr/forge:1.0.0-cli'],
            'role tag'    => ['COMPOSE_FIXTURE_IMAGE' => 'localhost/brnshkr/forge:cli'],
            'own service' => ['COMPOSE_FIXTURE_IMAGE' => 'acme/app:1', 'COMPOSE_FIXTURE_SERVICE' => 'hadolint'],
        ] as $name => $environment) {
            $listings[$name] = $this->runMake(['help', 'vv', 'APP_SERVICE=php-app'], [
                'DOCKER'          => $lintersDirectory . '/bin/docker',
                'IS_IN_CONTAINER' => '',
                ...$environment,
            ], $lintersDirectory);
        }

        self::assertDoesNotContainSymbol('hadolint-list', $listings['other image']);
        self::assertDoesNotContainSymbol('actionlint-list', $listings['other image']);
        self::assertContainsSymbol('hadolint-list', $listings['forge image']);
        self::assertDoesNotContainSymbol('hadolint-list', $listings['forge app']);
        self::assertContainsSymbol('hadolint-list', $listings['local forge']);
        self::assertContainsSymbol('hadolint-list', $listings['role tag']);
        self::assertContainsSymbol('actionlint-list', $listings['forge image']);
        self::assertContainsSymbol('hadolint-list', $listings['own service']);
        self::assertDoesNotContainSymbol('actionlint-list', $listings['own service']);
    }

    public function testChangelogRefusesATagItWouldPasteIntoTheShell(): void
    {
        $result = $this->runMake(
            ['changelog', 'GIT=printf \'%s\n\' \'v1;false\''],
            directory: self::CONSUMER_DIRECTORY,
            doExpectFailure: true,
        );

        self::assertStringContainsString('`v1;false` does not name a tag', $result);
    }

    public function testTheFirstReleaseShowsTheInitialText(): void
    {
        $consumerDirectory = $this->getFixtureCopy(self::CONSUMER_DIRECTORY);

        self::commitInto($consumerDirectory, 'feat(user): import users');

        $result = $this->runMake(['changelog', 'VERSION=1.0.0'], directory: $consumerDirectory);

        self::assertStringContainsString("## 1.0.0\n\nInitial release\n", $result);
        self::assertStringNotContainsString('import users', $result);
    }

    public function testOnlyTheFirstReleaseShowsTheInitialText(): void
    {
        $consumerDirectory = $this->getFixtureCopy(self::CONSUMER_DIRECTORY);

        self::commitInto($consumerDirectory, 'feat(user): import users');
        self::tagInto($consumerDirectory, 'v1.0.0');
        self::commitInto($consumerDirectory, 'fix(email): keep address casing');

        $result = $this->runMake(['changelog', 'all', 'VERSION=1.1.0'], directory: $consumerDirectory);

        self::assertStringContainsString("## v1.0.0\n\nInitial release\n", $result);
        self::assertStringContainsString('keep address casing', $result);
        self::assertStringNotContainsString('import users', $result);
    }

    public function testChangelogReportsWhetherItCreatedUpdatedOrLeftTheFile(): void
    {
        $consumerDirectory = $this->getFixtureCopy(self::CONSUMER_DIRECTORY);
        $write             = ['changelog', 'write', 'VERSION=1.0.0', 'CHANGELOG_INITIAL='];

        self::commitInto($consumerDirectory, 'feat(user): import users');

        $created   = $this->runMake($write, directory: $consumerDirectory);
        $unchanged = $this->runMake($write, directory: $consumerDirectory);

        self::commitInto($consumerDirectory, 'fix(email): keep address casing');

        $updated = $this->runMake($write, directory: $consumerDirectory);

        self::assertStringContainsString('Created `./changelog/1.x.md`.', $created);
        self::assertStringContainsString('Nothing written.', $unchanged);
        self::assertStringContainsString('Updated `./changelog/1.x.md`.', $updated);

        self::assertStringContainsString(
            'keep address casing',
            new Filesystem()->readFile($consumerDirectory . '/changelog/1.x.md'),
        );
    }

    public function testAForcedChangelogRebuildsItsSectionsAndKeepsTheNotes(): void
    {
        $consumerDirectory = $this->getFixtureCopy(self::CONSUMER_DIRECTORY);
        $changelogPath     = $consumerDirectory . '/changelog/1.x.md';
        $write             = ['changelog', 'write', 'VERSION=1.0.0', 'CHANGELOG_INITIAL='];
        $filesystem        = new Filesystem();

        self::commitInto($consumerDirectory, 'feat(user): import users');
        self::commitInto($consumerDirectory, 'fix(email): keep address casing');
        $this->runMake($write, directory: $consumerDirectory);

        $generated  = $filesystem->readFile($changelogPath);
        $movedEntry = Str::trim(Str::afterLast($generated, "#### User\n\n"));

        $handMangling = [
            "## 1.0.0\n"                      => "## 1.0.0 (hand-written date)\n",
            "#### Email\n\n"                  => "#### Emial\n\nHand-written note.\n\n",
            "\n\n#### User\n\n" . $movedEntry => "\n" . $movedEntry . "\n\n#### User",
        ];

        $mangled = $generated;

        foreach ($handMangling as $needle => $replacement) {
            $mangled = Str::replace($mangled, $needle, $replacement);
        }

        $filesystem->dumpFile($changelogPath, $mangled);
        $this->runMake([...$write, 'force'], directory: $consumerDirectory);

        self::assertSame($generated . "\nHand-written note.\n", $filesystem->readFile($changelogPath));
    }

    public function testAnArgumentIsWeighedWithoutRunningIt(): void
    {
        $lintersDirectory = $this->getFixtureCopy(self::LINTERS_DIRECTORY);

        $this->runLinter(['hadolint', '--', '$(touch${IFS}positional-ran)'], doExpectFailure: true);

        self::assertFileDoesNotExist($lintersDirectory . '/positional-ran');
    }

    public function testImageTakesTheProjectsBuildSettings(): void
    {
        $default = $this->runLinter(['-n', 'image', 'IMAGE_NAME=app']);
        $unnamed = $this->runLinter(['-n', 'image', 'IMAGE_NAME=shop']);

        $configured = $this->runLinter([
            '-n',
            'image',
            'IMAGE_NAME=app',
            'IMAGE_CONTEXT=docker',
            'IMAGE_REFERENCE=registry.acme.internal/shop:1.0.0',
            'IMAGE_FLAGS=--platform=linux/arm64 --push',
        ]);

        self::assertStringContainsString('ls-files -z --cached --deduplicate', $default);
        self::assertStringContainsString('buildx build --load -f \'./docker/app.Dockerfile\'', $default);
        self::assertStringContainsString('-t \'localhost/app:latest\' \'.cache/image.context\'', $default);
        self::assertStringContainsString('-f \'./Dockerfile\'', $unnamed);

        self::assertStringNotContainsString('ls-files -z --cached --deduplicate', $configured);
        self::assertStringContainsString('buildx build --platform=linux/arm64 --push -f \'./docker/app.Dockerfile\'', $configured);
        self::assertStringContainsString('-t \'registry.acme.internal/shop:1.0.0\' \'docker\'', $configured);
    }

    public function testDeployRunsForgesScriptAgainstTheStage(): void
    {
        $deployDirectory = $this->getFixtureCopy(self::DEPLOY_DIRECTORY);
        $env             = ['PATH' => $deployDirectory . '/bin:' . Str::fromEnvironment('PATH')];

        $rollback  = $this->runMake(['production-deploy-rollback'], $env, $deployDirectory);
        $versioned = $this->runMake(['production-deploy', 'VERSION=1.2.3'], $env, $deployDirectory);
        $unstaged  = $this->runMake(['deploy'], $env, $deployDirectory, doExpectFailure: true);
        $agent     = $this->runMake(['production-deploy'], [...$env, 'AI_AGENT' => 'acme-agent'], $deployDirectory, doExpectFailure: true);

        self::assertStringContainsString('deploy.sh rollback production', $rollback);
        self::assertStringContainsString('DEPLOY_DOTENV_BASE=' . $deployDirectory . '/.env', $rollback);
        self::assertStringContainsString("VERSION=\n", $rollback);
        self::assertStringContainsString('deploy.sh deploy production', $versioned);
        self::assertStringContainsString('VERSION=1.2.3', $versioned);
        self::assertStringContainsString('`DEPLOY_HOSTS` is empty, so run it in a stage that names hosts', $unstaged);
        self::assertStringContainsString('`AI_AGENT` or `CLAUDECODE` is set, so run the deploy yourself', $agent);
        self::assertStringNotContainsString('deploy.sh deploy production', $agent);
    }

    public function testSemgrepResolvesItsRulesets(): void
    {
        $scenarios = [];

        foreach ([
            'detected'   => [],
            'shipped'    => ['SEMGREP_CONFIG=conf/shipped.yaml'],
            'overridden' => ['SEMGREP_CONFIG=conf/override.yaml'],
        ] as $name => $variables) {
            $scenarios[$name] = $this->runLinter(['semgrep-list', ...$variables]);
        }

        $this->assertMatchesSnapshot($this->renderScenarios($scenarios));
    }

    public function testSemgrepLeavesRegexDosToTheRegexpPlugin(): void
    {
        $lintersDirectory = $this->getFixtureCopy(self::LINTERS_DIRECTORY);
        $filesystem       = new Filesystem();
        $regexDosIgnore   = '\'=ajinabraham.njsscan.dos.regex_dos.regex_dos\'';

        $semgrepDryRunArguments = [
            '-n',
            'semgrep',
            'SEMGREP_RULESETS=default',
            'SEMGREP_CONFIG=conf/shipped.yaml',
        ];

        $filesystem->dumpFile($lintersDirectory . '/node_modules/eslint-plugin-regexp/package.json', '{}');

        $outputWithoutEslint = $this->runLinter($semgrepDryRunArguments);

        $filesystem->dumpFile($lintersDirectory . '/node_modules/.bin/eslint', '');
        $filesystem->dumpFile($lintersDirectory . '/index.js', '');

        $outputWithEslint = $this->runLinter($semgrepDryRunArguments);

        $filesystem->remove($lintersDirectory . '/node_modules/eslint-plugin-regexp');

        $outputWithoutPlugin = $this->runLinter($semgrepDryRunArguments);

        self::assertStringNotContainsString($regexDosIgnore, $outputWithoutEslint);
        self::assertStringContainsString($regexDosIgnore, $outputWithEslint);
        self::assertStringNotContainsString($regexDosIgnore, $outputWithoutPlugin);
    }

    #[Group('python')]
    public function testSemgrepSaysWhenARefetchChangedARuleset(): void
    {
        $cachedPath = self::LINTERS_DIRECTORY . '/.cache/semgrep.cache/default.json';
        $filesystem = new Filesystem();

        $filesystem->dumpFile($cachedPath, '{"rules":[{"id":"old"}]}');
        $filesystem->touch($cachedPath, time() - 8 * 86_400);

        $output = $this->runMakeIn(self::MODE_RUN, [
            'semgrep',
            'APP_SERVICE=php-app',
            'SEMGREP_RULESETS=default',
            'SEMGREP_CONFIG=conf/shipped.yaml',
            'CURL=bin/curl',
            'SEMGREP=bin/semgrep',
        ], fixtureDirectory: self::LINTERS_DIRECTORY);

        $finder = new Finder()
            ->in(Path::getDirectory($cachedPath))
            ->depth(0)
            ->ignoreDotFiles(false)
            ->sortByName()
        ;

        self::assertStringContainsString('The `default` ruleset changed since it was last fetched.', $output);

        self::assertSame(
            ['default.json', 'rules.json'],
            array_values(array_map(
                static fn (SplFileInfo $cachedFile): string => $cachedFile->getFilename(),
                [...$finder],
            )),
        );
    }

    /**
     * @param list<string> $args
     */
    private function runLinter(array $args, bool $doExpectFailure = false): string
    {
        $lintersDirectory = $this->getFixtureCopy(self::LINTERS_DIRECTORY);

        return $this->runMake(
            $args,
            ['PATH' => $lintersDirectory . '/bin:' . Str::fromEnvironment('PATH')],
            $lintersDirectory,
            $doExpectFailure,
        );
    }
}
