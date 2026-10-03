<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;

use function array_map;
use function array_values;
use function dirname;
use function time;

/**
 * @internal
 */
#[CoversNothing]
final class ToolsTest extends TestCase
{
    use MakeTrait;
    use MatchesSnapshots;

    private const string LINTERS_DIRECTORY  = __DIR__ . '/../Fixtures/Make/Linters';
    private const string COVERAGE_DIRECTORY = __DIR__ . '/../Fixtures/Make/Coverage';
    private const string SETTINGS_DIRECTORY = __DIR__ . '/../Fixtures/Make/Settings';

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
        self::assertStringContainsString('none.txt is missing', $absent);
        self::assertStringNotContainsString('coverage is below', $absent);
        self::assertStringContainsString('Methods', $metric);
        self::assertStringContainsString('95%', $metric);
    }

    public function testTheRectorRulesInUseAreListed(): void
    {
        $result = $this->runMake(['rector-print'], directory: self::PROJECT_DIRECTORY);

        self::assertStringStartsWith("Loaded rector rules\n===================\n\n * Rector\\", $result);
        self::assertStringContainsString(' * Rector\DeadCode\Rector\\', $result);
        self::assertMatchesRegularExpression('/\nLoaded \d+ rules\n$/', $result);
    }

    public function testTheTwigCsFixerRulesInUseAreListed(): void
    {
        $result = $this->runMake(['twig-cs-fixer-print'], directory: self::PROJECT_DIRECTORY);

        self::assertStringStartsWith(
            "Loaded twig-cs-fixer rules\n==========================\n\n * TwigCsFixer\\",
            $result,
        );

        self::assertMatchesRegularExpression('/\nLoaded \d+ rules\n$/', $result);
    }

    public function testEveryPestTargetRefusesWhenPestIsNotInstalled(): void
    {
        foreach (['pest', 'pest-debug', 'pest-list', 'pest-update', 'pest-coverage'] as $target) {
            $result = $this->runMake(
                [$target],
                directory: __DIR__ . '/../Fixtures/Make/Guard',
                doExpectFailure: true,
            );

            self::assertStringContainsString('pest is not installed', $result, $target);
        }
    }

    public function testHadolintResolvesItsDockerfiles(): void
    {
        $this->assertMatchesSnapshot($this->runLinter(['hadolint-list']));
    }

    public function testActionlintResolvesItsWorkflows(): void
    {
        $this->assertMatchesSnapshot($this->runLinter(['actionlint-list']));
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

    public function testChangelogReportsWhetherItCreatedUpdatedOrLeftTheFile(): void
    {
        $consumerDirectory = $this->getFixtureCopy(self::CONSUMER_DIRECTORY);
        $write             = ['changelog', 'write', 'VERSION=1.0.0'];

        self::commitInto($consumerDirectory, 'feat(user): import users');

        $created   = $this->runMake($write, directory: $consumerDirectory);
        $unchanged = $this->runMake($write, directory: $consumerDirectory);

        self::commitInto($consumerDirectory, 'fix(email): keep address casing');

        $updated = $this->runMake($write, directory: $consumerDirectory);

        self::assertStringContainsString('Created ./changelog/1.x.md.', $created);
        self::assertStringContainsString('Nothing written.', $unchanged);
        self::assertStringContainsString('Updated ./changelog/1.x.md.', $updated);

        self::assertStringContainsString(
            'keep address casing',
            new Filesystem()->readFile($consumerDirectory . '/changelog/1.x.md'),
        );
    }

    public function testAnArgumentIsWeighedWithoutRunningIt(): void
    {
        $lintersDirectory = $this->getFixtureCopy(self::LINTERS_DIRECTORY);

        $this->runLinter(['hadolint', '--', '$(touch${IFS}positional-ran)'], doExpectFailure: true);

        self::assertFileDoesNotExist($lintersDirectory . '/positional-ran');
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

    public function testSemgrepSaysWhenARefetchChangedARuleset(): void
    {
        $cachedPath = $this->getFixtureCopy(self::LINTERS_DIRECTORY) . '/.cache/semgrep/default.json';
        $filesystem = new Filesystem();

        $filesystem->dumpFile($cachedPath, '{"rules":[{"id":"old"}]}');
        $filesystem->touch($cachedPath, time() - 8 * 86_400);

        $output = $this->runLinter(['semgrep', 'SEMGREP_RULESETS=default', 'SEMGREP_CONFIG=conf/shipped.yaml']);

        $finder = new Finder()
            ->in(dirname($cachedPath))
            ->depth(0)
            ->ignoreDotFiles(false)
            ->sortByName()
        ;

        self::assertStringContainsString('The default ruleset changed since it was last fetched.', $output);

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

    private static function commitInto(string $directory, string $message): void
    {
        new Process([
            'git',
            '-c',
            'user.name=Acme',
            '-c',
            'user.email=dev@acme.test',
            '-c',
            'commit.gpgsign=false',
            'commit',
            '--quiet',
            '--allow-empty',
            '--no-verify',
            '--message',
            $message,
        ], $directory)->mustRun();
    }
}
