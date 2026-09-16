<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Json;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function array_diff;
use function array_first;
use function array_map;
use function array_unique;
use function count;
use function dirname;
use function getenv;
use function is_dir;
use function is_file;
use function is_link;
use function md5;
use function mkdir;
use function readlink;
use function rmdir;
use function scandir;
use function shell_exec;
use function sprintf;
use function Symfony\Component\String\s;
use function unlink;

/**
 * @internal
 */
#[CoversNothing]
final class MakefileTest extends TestCase
{
    use MatchesSnapshots;

    private const string MAKEFILE_PATH      = __DIR__ . '/../../conf/Makefile';
    private const string FIXTURES_DIRECTORY = __DIR__ . '/Fixtures/Make/Help';
    private const string DOTENV_DIRECTORY   = __DIR__ . '/Fixtures/Make/Dotenv';
    private const string CONSUMER_DIRECTORY = __DIR__ . '/Fixtures/Make/Consumer';
    private const string CONFIGS_DIRECTORY  = __DIR__ . '/Fixtures/Make/Configs';
    private const string TSCONFIG_DIRECTORY = __DIR__ . '/Fixtures/Make/Typescript';
    private const string RESERVED_DIRECTORY = __DIR__ . '/Fixtures/Make/ReservedScope';
    private const string CACHES_DIRECTORY   = __DIR__ . '/Fixtures/Make/Caches';
    private const string COVERAGE_DIRECTORY = __DIR__ . '/Fixtures/Make/Coverage';
    private const string FALLBACK_DIRECTORY = __DIR__ . '/Fixtures/Make/ConfigFallback';
    private const string VENDOR_DIRECTORY   = __DIR__ . '/Fixtures/Make/ConfigVendor';
    private const string STARTUP_DIRECTORY  = __DIR__ . '/Fixtures/Make/Startup';
    private const string SEARCH_DIRECTORY   = __DIR__ . '/Fixtures/Make/ConfigSearch';

    private const array CONFIG_DIRECTORIES = [
        self::CONFIGS_DIRECTORY,
        self::FALLBACK_DIRECTORY,
        self::VENDOR_DIRECTORY,
        self::STARTUP_DIRECTORY,
        self::TSCONFIG_DIRECTORY,
    ];

    /**
     * Deterministic environment baseline for every help invocation. Tests merge
     * scenario-specific overrides on top via `runMakeHelp(env: [...])`.
     */
    private const array BASELINE_ENV = [
        'MAKEFLAGS'         => '',
        'NO_ANSI'           => '1',
        'WSL_DISTRO_NAME'   => '',
        'TERM_PROGRAM'      => '',
        'TERMINAL_EMULATOR' => '',
        'EDITOR'            => '',
        'LANG'              => '',
    ];

    /**
     * Snapshot scenarios. Each scenario produces one rendering of `make help`;
     * identical outputs are de-duplicated in the snapshot, with the surviving
     * scenarios listed in the `groups` manifest at the top.
     */
    private const array SNAPSHOT_SCENARIOS = [
        'default'                => [],
        'verbosity-v'            => ['args' => ['v']],
        'verbosity-v-alias'      => ['args' => ['--', '-v']],
        'verbosity-vv'           => ['args' => ['vv']],
        'verbosity-vv-alias'     => ['args' => ['--', '-vv']],
        'verbosity-vvv'          => ['args' => ['vvv']],
        'verbosity-vvv-alias'    => ['args' => ['--', '-vvv']],
        'verbosity-vvvv'         => ['args' => ['vvvv']],
        'verbosity-V-env-0'      => ['env' => ['V' => '0']],
        'verbosity-V-env-3'      => ['env' => ['V' => '3']],
        'verbosity-V-env-10'     => ['env' => ['V' => '10']],
        'verbosity-V-env-empty'  => ['args' => ['vv'], 'env' => ['V' => '']],
        'list-scopes'            => ['args' => ['list-scopes']],
        'resolve'                => ['args' => ['resolve']],
        'resolve-alias'          => ['args' => ['--', '-r']],
        'list-scopes-alias'      => ['args' => ['ls']],
        'list-scopes-vvvv'       => ['args' => ['ls', 'vvvv']],
        'scope-filter-single'    => ['args' => ['app.commands']],
        'scope-filter-group'     => ['args' => ['app']],
        'scope-filter-multiple'  => ['args' => ['app.commands', 'local']],
        'scope-filter-union'     => ['args' => ['app.commands', 'app.variables']],
        'scope-filter-verbosity' => ['args' => ['app.variables', '--', 'vv']],
        'ifdef-defined'          => ['args' => ['vvv'], 'env' => ['IS_DEFINED' => '1']],
        'else-ifeq-de'           => ['args' => ['vvv'], 'env' => ['LANG' => 'de']],
        'else-ifeq-fr'           => ['args' => ['vvv'], 'env' => ['LANG' => 'fr']],
        'override-logo'          => ['env' => ['LOGO' => "CUSTOM LOGO LINE 1\nCUSTOM LOGO LINE 2"]],
        'override-package'       => ['env' => ['VENDOR' => 'acme', 'PACKAGE' => 'tool-kit']],
        'override-version'       => ['env' => ['VERSION' => '99.0.0-rc.1']],
        'no-logo'                => ['env' => ['LOGO' => '']],
        'no-package'             => ['env' => ['PACKAGE' => '']],
        'no-version'             => ['env' => ['VERSION' => '']],
        'no-header'              => ['env' => ['LOGO' => '', 'PACKAGE' => '', 'VERSION' => '']],
        'editor-empty'           => ['args' => ['vvv'], 'env' => ['EDITOR' => '']],
        'theme-brnshkr-explicit' => ['args' => ['vvv'], 'env' => ['THEME' => 'brnshkr', 'NO_ANSI' => '']],
        'theme-symfony'          => ['args' => ['vvv'], 'env' => ['THEME' => 'symfony', 'NO_ANSI' => '']],
        'theme-unknown'          => ['args' => ['vvv'], 'env' => ['THEME' => 'bogus', 'NO_ANSI' => '']],
    ];

    /**
     * Environment-file scenarios. Each resolves the fixture's `.env` family a different way.
     */
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

    public function testHelpOutput(): void
    {
        $scenarios = [];

        foreach (self::SNAPSHOT_SCENARIOS as $name => $scenario) {
            $scenarios[$name] = $this->runMakeHelp(
                $scenario['args'] ?? [],
                $scenario['env'] ?? [],
            );
        }

        $this->assertMatchesSnapshot($this->renderScenarios($scenarios));
    }

    public function testUnknownScopeReportsError(): void
    {
        $result = $this->runMakeHelp(['nonexistent.scope'], doExpectFailure: true);

        self::assertStringContainsString('Unknown scope', $result);
        self::assertStringContainsString('nonexistent.scope', $result);
    }

    public function testUnsupportedEditorGivenDeliberatelyReportsError(): void
    {
        $result = $this->runMake(['help', 'EDITOR=nano'], doExpectFailure: true);

        self::assertStringContainsString('Unknown editor', $result);
        self::assertStringContainsString('"nano"', $result);
    }

    public function testUnsupportedEditorFromTheEnvironmentIsIgnored(): void
    {
        $result = $this->runMakeHelp(env: ['EDITOR' => 'vim']);

        self::assertStringNotContainsString('Unknown editor', $result);
    }

    public function testAutoIncludeCoversEverySupportedDirectory(): void
    {
        $result = $this->runMakeHelp(['vvv']);

        foreach ([
            'root-makefile-command',
            'root-mk-command',
            'conf-makefile-command',
            'conf-mk-command',
            'conf-make-makefile-command',
            'conf-make-mk-command',
            'local-makefile-command',
            'local-mk-command',
            'local-make-makefile-command',
            'local-make-mk-command',
        ] as $symbol) {
            self::assertContainsSymbol($symbol, $result);
        }
    }

    public function testFilesOutsideAutoIncludePathsAreNotIncluded(): void
    {
        $result = $this->runMakeHelp(['vvv']);

        foreach ([
            'not-included-command',
            'excluded-makefile-command',
            'excluded-mk-command',
            'make-makefile-command',
            'make-mk-command',
        ] as $symbol) {
            self::assertDoesNotContainSymbol($symbol, $result);
        }
    }

    public function testPrivateSymbolsAreNeverRendered(): void
    {
        $result = $this->runMakeHelp(['vvvv']);

        foreach ([
            '_PRIVATE_VARIABLE',
            '_private_fn',
            '.dot-command',
            '_under-command',
        ] as $symbol) {
            self::assertStringNotContainsString($symbol, $result, sprintf('private symbol %s leaked into output', $symbol));
        }
    }

    public function testAnsiEscapesAreEmittedWhenColorsAreEnabled(): void
    {
        $withColors    = $this->runMakeHelp(env: ['NO_ANSI' => '']);
        $withoutColors = $this->runMakeHelp();

        self::assertStringContainsString("\033[", $withColors);
        self::assertStringNotContainsString("\033[", $withoutColors);
    }

    public function testEditorHyperlinksMatchSelectedEditor(): void
    {
        $vscode    = $this->runMakeHelp(['vvv'], ['NO_ANSI' => '', 'EDITOR' => 'vscode']);
        $vscodeWsl = $this->runMakeHelp(['vvv'], ['NO_ANSI' => '', 'EDITOR' => 'vscode', 'WSL_DISTRO_NAME' => 'Ubuntu']);
        $phpstorm  = $this->runMakeHelp(['vvv'], ['NO_ANSI' => '', 'EDITOR' => 'phpstorm']);

        self::assertStringContainsString("\033]8;;vscode://file/", $vscode);
        self::assertStringContainsString("\033]8;;vscode://vscode-remote/wsl+Ubuntu/", $vscodeWsl);
        self::assertStringContainsString("\033]8;;phpstorm://open?file=", $phpstorm);
    }

    public function testDebugFlagEchoesRecipeLines(): void
    {
        $withDebug    = $this->runMakeHelp(env: ['DEBUG' => '1']);
        $withoutDebug = $this->runMakeHelp();

        self::assertStringContainsString('_CURDIR=', $withDebug);
        self::assertStringNotContainsString('_CURDIR=', $withoutDebug);
    }

    public function testEveryAwkImplementationProducesIdenticalOutput(): void
    {
        $implementations = [
            'gawk'        => 'gawk',
            'mawk'        => 'mawk',
            'busybox awk' => 'busybox',
        ];

        $outputs = [];

        foreach ($implementations as $awk => $binary) {
            if (shell_exec(sprintf('command -v %s', $binary)) === null) {
                continue;
            }

            $outputs[$awk] = $this->runMakeHelp(['vvv'], ['AWK' => $awk]);
        }

        self::assertGreaterThan(1, count($outputs), 'fewer than two awk implementations are installed');
        self::assertCount(1, array_unique($outputs), 'awk implementations disagree');
    }

    public function testIfeqBranchSelectionIsCorrect(): void
    {
        $withProd    = $this->runMakeHelp(env: ['ENV' => 'prod']);
        $withDefault = $this->runMakeHelp();

        self::assertStringContainsString('prod-value', $withProd);
        self::assertStringNotContainsString('else-value', $withProd);
        self::assertStringContainsString('branch-a', $withProd);
        self::assertStringNotContainsString('branch-b', $withProd);

        self::assertStringContainsString('else-value', $withDefault);
        self::assertStringNotContainsString('prod-value', $withDefault);
        self::assertStringContainsString('branch-b', $withDefault);
        self::assertStringNotContainsString('branch-a', $withDefault);
    }

    public function testElseIfdefChainSelectsCorrectBranch(): void
    {
        $withIsDefined = $this->runMakeHelp(env: ['IS_DEFINED' => '1']);
        $withLang      = $this->runMakeHelp(env: ['LANG' => 'de']);
        $withNeither   = $this->runMakeHelp();

        self::assertStringContainsString('from-ifdef-branch', $withIsDefined);
        self::assertStringNotContainsString('from-else-ifdef-branch', $withIsDefined);
        self::assertStringNotContainsString('from-else-branch', $withIsDefined);

        self::assertStringContainsString('from-else-ifdef-branch', $withLang);
        self::assertStringNotContainsString('from-ifdef-branch', $withLang);

        self::assertStringContainsString('from-else-branch', $withNeither);
        self::assertStringNotContainsString('from-ifdef-branch', $withNeither);
    }

    public function testIfndefChainAlwaysSelectsPrimaryBranch(): void
    {
        foreach ([
            'never-defined-unset' => [],
            'never-defined-set'   => ['NEVER_DEFINED' => '1'],
            'lang-set'            => ['LANG' => 'de'],
            'both-set'            => ['NEVER_DEFINED' => '1', 'LANG' => 'de'],
        ] as $label => $env) {
            $result = $this->runMakeHelp(env: $env);

            self::assertStringContainsString('from-ifndef-branch', $result, sprintf('%s: primary branch missing', $label));
            self::assertStringNotContainsString('from-else-ifndef-branch', $result, sprintf('%s: else branch leaked', $label));
        }
    }

    public function testDotenvResolution(): void
    {
        $scenarios = [];

        foreach (self::DOTENV_SCENARIOS as $name => $environment) {
            $scenarios[$name] = $this->runMake(
                ['dotenv-show'],
                env: $environment,
                directory: self::DOTENV_DIRECTORY,
            );
        }

        $this->assertMatchesSnapshot($this->renderScenarios($scenarios));
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

    public function testFixWritesCheckOnlyReadsAndTestOnlyTests(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Verbs';
        $fix       = $this->runMake(['fix'], directory: $directory);
        $check     = $this->runMake(['check'], directory: $directory);
        $test      = $this->runMake(['test'], directory: $directory);

        self::assertStringContainsString('php-cs-fixer fix', $fix);
        self::assertStringNotContainsString('--dry-run', $fix);
        self::assertStringNotContainsString('phpstan', $fix);
        self::assertStringNotContainsString('phpunit', $fix);
        self::assertStringContainsString('--dry-run', $check);
        self::assertStringContainsString('phpstan analyze', $check);
        self::assertStringNotContainsString('phpunit', $check);
        self::assertStringContainsString('phpunit', $test);
        self::assertStringNotContainsString('php-cs-fixer', $test);
        self::assertStringNotContainsString('phpstan', $test);
    }

    public function testCiRunsItsTargetsInTheOrderTheyAreListed(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Verbs';
        $default   = $this->runMake(['ci'], directory: $directory);
        $fixing    = $this->runMake(['ci'], ['CI_TARGETS' => 'fix check test'], $directory);

        self::assertMatchesRegularExpression('/--dry-run\n.*phpstan analyze.*\n.*phpunit/s', $default);
        self::assertStringNotContainsString(" -v\n", $default);
        self::assertMatchesRegularExpression('/php-cs-fixer fix [^\n]* -v\n.*--dry-run\n.*phpunit/s', $fixing);
    }

    public function testFixRunsRectorBeforePhpCsFixerEvenInParallel(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Verbs';

        foreach ([[], ['-j4']] as $flags) {
            $fix = $this->runMake([...$flags, 'fix'], directory: $directory);

            self::assertMatchesRegularExpression('/rector done\n(?:.*\n)*php-cs-fixer fix/', $fix);
            self::assertStringNotContainsString('warning', $fix);
        }
    }

    public function testSnapshotsAreUpdatedThroughTheRunnersOwnMechanism(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Verbs';
        $phpunit   = $this->runMake(['phpunit-update'], directory: $directory);
        $pest      = $this->runMake(['phpunit-update'], ['PHP_UNIT' => 'echo pest'], $directory);

        self::assertStringContainsString('update-snapshots --configuration', $phpunit);
        self::assertStringContainsString('--do-not-fail-on-incomplete', $phpunit);
        self::assertStringNotContainsString('-d --update-snapshots', $phpunit);
        self::assertStringContainsString('--update-snapshots --do-not-fail-on-incomplete', $pest);
    }

    public function testEveryBunToolRunsItsBinaryRatherThanAScriptOfTheSameName(): void
    {
        $help = $this->runMake(['help', 'resolve', 'v'], directory: __DIR__ . '/Fixtures/Make/Bun');

        foreach (['commitlint', 'eslint', 'markdownlint-cli2', 'stylelint', 'tsc', 'vitest'] as $binary) {
            self::assertStringContainsString('bun --bun x ' . $binary . ' ', $help);
        }
    }

    public function testAVerbAnnouncesEachTargetItRunsAndNothingElseDoes(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Verbs';
        $check     = $this->runMake(['check'], directory: $directory);
        $fix       = $this->runMake(['fix'], directory: $directory);
        $ci        = $this->runMake(['ci'], directory: $directory);
        $single    = $this->runMake(['rector-dry-run'], directory: $directory);

        self::assertStringContainsString("[acme/verbs] Running rector-dry-run\nrector process", $check);
        self::assertStringContainsString("[acme/verbs] Running phpstan\nphpstan analyze", $check);
        self::assertStringNotContainsString("Running rector\n", $check);
        self::assertStringContainsString("[acme/verbs] Running rector\nrector process", $fix);
        self::assertStringNotContainsString('Running _', $fix);
        self::assertStringContainsString("[acme/verbs] Running check\n", $ci);
        self::assertStringContainsString("[acme/verbs] Running test\n", $ci);
        self::assertStringNotContainsString('Running', $single);
    }

    public function testAnAnnouncementCanBeRewordedOrSilenced(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Verbs';
        $reworded  = $this->runMake(['check'], ['ANNOUNCEMENT' => '>>> %s'], $directory);
        $silenced  = $this->runMake(['check'], ['ANNOUNCEMENT' => ''], $directory);

        self::assertStringContainsString("[acme/verbs] >>> phpstan\n", $reworded);
        self::assertStringNotContainsString('[acme/verbs]', $silenced);
    }

    public function testAVerbRunsEveryToolPastAFailureWhileCiAndAFixChainStop(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Verbs';
        $check     = $this->runMake(['check'], ['PHP_CS_FIXER' => 'false'], $directory, doExpectFailure: true);
        $ci        = $this->runMake(['ci'], ['PHP_CS_FIXER' => 'false'], $directory, doExpectFailure: true);
        $fix       = $this->runMake(['fix'], ['RECTOR' => 'false'], $directory, doExpectFailure: true);

        self::assertStringContainsString('phpstan done', $check);
        self::assertStringContainsString('phpstan done', $ci);
        self::assertStringNotContainsString('Running test', $ci);
        self::assertStringNotContainsString('php-cs-fixer fix', $fix);
    }

    public function testAParallelCheckPrintsEachToolWhole(): void
    {
        $check = $this->runMake(['-j4', 'check'], directory: __DIR__ . '/Fixtures/Make/Verbs');

        self::assertMatchesRegularExpression('/Running php-cs-fixer-dry-run\nphp-cs-fixer fix [^\n]*--dry-run\nphp-cs-fixer done/', $check);
        self::assertMatchesRegularExpression('/Running rector-dry-run\nrector process [^\n]*--dry-run\nrector done/', $check);
        self::assertMatchesRegularExpression('/Running phpstan\nphpstan analyze [^\n]*\nphpstan done/', $check);
    }

    public function testAVerbWithNothingToRunSaysSo(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Consumer';

        self::assertStringContainsString('No fixer to run.', $this->runMake(['fix'], directory: $directory));
        self::assertStringContainsString('No tool to run.', $this->runMake(['check'], directory: $directory));
        self::assertStringContainsString('No test to run.', $this->runMake(['test'], directory: $directory));
    }

    public function testTheScopeListNamesOnlyScopesWithSomethingToShow(): void
    {
        $plain   = $this->runMakeHelp(['ls']);
        $verbose = $this->runMakeHelp(['ls', 'vvvv']);

        self::assertStringContainsString("app.commands\n", $plain);
        self::assertStringNotContainsString("app.verbose-group\n", $plain);
        self::assertStringContainsString("app.hyper-verbose-group\n", $verbose);
        self::assertStringNotContainsString('app.empty-group', $verbose);
        self::assertStringNotContainsString('brnshkr.ansi', $verbose);
    }

    public function testFilteringByAScopeHiddenAtThisVerbosityNamesTheLevel(): void
    {
        $hiddenEntries = $this->runMakeHelp(['app.hidden-group'], doExpectFailure: true);
        $hiddenScope   = $this->runMakeHelp(['app.very-verbose-group'], doExpectFailure: true);
        $visible       = $this->runMakeHelp(['app.hidden-group', 'app.very-verbose-group', 'vv']);

        self::assertStringContainsString('Scope "app.hidden-group" shows nothing below vv.', $hiddenEntries);
        self::assertStringContainsString('Scope "app.very-verbose-group" shows nothing below vv.', $hiddenScope);
        self::assertStringContainsString('HIDDEN_GROUP_VARIABLE', $visible);
        self::assertStringContainsString('very-verbose-command', $visible);
    }

    public function testFilteringByAScopeWithNothingToShowIsRefused(): void
    {
        $result = $this->runMakeHelp(['app.empty-group', 'vvvv'], doExpectFailure: true);

        self::assertStringContainsString('Unknown scope', $result);
        self::assertStringContainsString('app.empty-group', $result);
    }

    public function testACollidingNameStaysWithTheProjectAndTheSharedOneMovesAside(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Collision';

        self::assertStringContainsString('collision', $this->runMake(['check'], directory: $directory));
        self::assertContainsSymbol('brnshkr-check', $this->runMake(['help'], directory: $directory));
    }

    public function testACollisionInsideAChosenNamespaceIsRefused(): void
    {
        $result = $this->runMake(
            ['shared-check'],
            directory: __DIR__ . '/Fixtures/Make/PrefixedCollision',
            doExpectFailure: true,
        );

        self::assertStringContainsString('shared-check is defined in', $result);
        self::assertStringContainsString('TARGET_PREFIX', $result);
    }

    public function testATargetInsideAConditionalTheProjectNeverTookIsNotOffered(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Conditionals';

        foreach (['gate-any-present', 'gate-nested-present', 'gate-odd-characters', 'gate-ifeq-other'] as $target) {
            self::assertStringContainsString($target, $this->runMake([$target], directory: $directory));
        }

        foreach (['gate-none-present', 'gate-nested-absent'] as $target) {
            $result = $this->runMake([$target], directory: $directory, doExpectFailure: true);

            self::assertStringContainsString('Unknown command', $result);
            self::assertStringContainsString($target, $result);
        }

        $help = $this->runMake(['help'], directory: $directory);
        $both = $this->runMake(['gate-any-present', 'gate-odd-characters'], directory: $directory);

        self::assertContainsSymbol('gate-odd-characters', $help);
        self::assertDoesNotContainSymbol('gate-none-present', $help);
        self::assertStringNotContainsString('warning', $both);
    }

    public function testHelpIsTheDefaultGoalUnlessTheProjectNamesItsOwn(): void
    {
        $shared = $this->runMake([], directory: self::CONSUMER_DIRECTORY);
        $own    = $this->runMake([], directory: __DIR__ . '/Fixtures/Make/DefaultGoal');

        self::assertStringContainsString('Available commands:', $shared);
        self::assertSame('own-default', Str::trim($own));
    }

    public function testTargetPrefixNamespacesOnlyTheSharedTargets(): void
    {
        $result = $this->runMake(['shared-help', 'TARGET_PREFIX=shared'], directory: self::CONSUMER_DIRECTORY);

        self::assertContainsSymbol('shared-help', $result);
        self::assertContainsSymbol('consumer-command', $result);
        self::assertDoesNotContainSymbol('shared-consumer-command', $result);
    }

    public function testPhonyKnobLimitsMarkingToTheSharedTargets(): void
    {
        $all    = $this->runMake(['-p'], directory: self::CONSUMER_DIRECTORY);
        $shared = $this->runMake(['-p', 'PHONY=shared'], directory: self::CONSUMER_DIRECTORY);
        $none   = $this->runMake(['-p', 'PHONY=0'], directory: self::CONSUMER_DIRECTORY);

        self::assertMatchesRegularExpression('/^\.PHONY:.* consumer-command /m', $all);
        self::assertMatchesRegularExpression('/^\.PHONY:(?!.* consumer-command ).* check /m', $shared);
        self::assertMatchesRegularExpression('/^\.PHONY:\s*$/m', $none);
    }

    public function testMissingConfigurationNamesThePathAndTheVariable(): void
    {
        $result = $this->runMake(['phpstan'], directory: __DIR__ . '/Fixtures/Make/Guard', doExpectFailure: true);

        self::assertStringContainsString('conf/phpstan.dist.php is missing', $result);
        self::assertStringContainsString('PHP_STAN_CONFIG', $result);
    }

    public function testGuardedPathIsTheOneOnThisMachineWhenTheToolsRunElsewhere(): void
    {
        $result = $this->runMake(
            ['phpstan', 'RUN=echo', 'PHP_STAN_CONFIG=/app/conf/phpstan.php'],
            directory: __DIR__ . '/Fixtures/Make/Guard',
            doExpectFailure: true,
        );

        self::assertStringContainsString('./conf/phpstan.php is missing', $result);
        self::assertStringNotContainsString('/app/conf/phpstan.php is missing', $result);
    }

    public function testConfigsWritesOnlyWhatTheProjectIsMissing(): void
    {
        $created = $this->runMake(['configs'], directory: self::CONFIGS_DIRECTORY);
        $kept    = $this->runMake(['configs'], directory: self::CONFIGS_DIRECTORY);

        self::assertStringContainsString('[acme/configs] Created', $created);
        self::assertStringContainsString('already exists', $kept);
        self::assertStringNotContainsString('Created', $kept);
        self::assertFileExists(self::CONFIGS_DIRECTORY . '/conf/phpstan.dist.php');
        self::assertFileDoesNotExist(self::CONFIGS_DIRECTORY . '/conf/phpstan.php');
        self::assertFileDoesNotExist(self::CONFIGS_DIRECTORY . '/conf/twig-cs-fixer.dist.php');
    }

    public function testTheTypescriptProjectIsWrittenToConfAndLinkedFromTheRoot(): void
    {
        $created = $this->runMake(['configs'], directory: self::TSCONFIG_DIRECTORY);
        $kept    = $this->runMake(['configs'], directory: self::TSCONFIG_DIRECTORY);

        self::assertStringContainsString('Created ./conf/tsconfig.json.', $created);
        self::assertStringContainsString('Linked ./tsconfig.json to ./conf/tsconfig.json.', $created);
        self::assertStringContainsString('./tsconfig.json already exists.', $kept);
        self::assertSame('conf/tsconfig.json', readlink(self::TSCONFIG_DIRECTORY . '/tsconfig.json'));
    }

    public function testTheTypescriptProjectFallsBackToAFileWhereLinkingFails(): void
    {
        $this->runMake(['configs'], ['LN' => 'false'], self::TSCONFIG_DIRECTORY);

        self::assertStringContainsString(
            '"extends": "./conf/tsconfig.json"',
            new Filesystem()->readFile(self::TSCONFIG_DIRECTORY . '/tsconfig.json'),
        );
    }

    public function testConfigsWritesThePrivateHalfOnlyWhenAskedTo(): void
    {
        foreach (['local', 'l'] as $spelling) {
            $this->runMake(['configs', $spelling], directory: self::CONFIGS_DIRECTORY);

            self::assertFileEquals(
                self::CONFIGS_DIRECTORY . '/conf/phpstan.php.example',
                self::CONFIGS_DIRECTORY . '/conf/phpstan.php',
                $spelling,
            );

            $this->removeWhatTheFixturesWrote();
        }
    }

    public function testThePrivateHalfIsWrittenFromATemplateWhenTheProjectHasNone(): void
    {
        $this->runMake(['configs', 'local'], directory: self::CONFIGS_DIRECTORY);

        $shim = new Filesystem()->readFile(self::CONFIGS_DIRECTORY . '/conf/php-cs-fixer.php');

        self::assertStringContainsString('$config = include __DIR__ . \'/php-cs-fixer.dist.php\';', $shim);
        self::assertStringContainsString('@internal App', $shim);
    }

    public function testAPrivateHalfThatCannotIncludeIsACopy(): void
    {
        $this->runMake(['configs', 'local'], directory: self::CONFIGS_DIRECTORY);

        self::assertFileEquals(
            self::CONFIGS_DIRECTORY . '/conf/phpunit.dist.xml',
            self::CONFIGS_DIRECTORY . '/conf/phpunit.xml',
        );
    }

    public function testNothingIsWrittenIntoAnInstallationOfThisPackage(): void
    {
        $this->runMake(['configs', 'local'], directory: self::VENDOR_DIRECTORY);

        self::assertFileExists(self::VENDOR_DIRECTORY . '/conf/phpstan.dist.php');
        self::assertFileDoesNotExist(self::VENDOR_DIRECTORY . '/vendor/brnshkr/config/conf/phpstan.php');
    }

    public function testTheConfigAToolReadsIsTheFirstOneThatIsThere(): void
    {
        $resolve = ['help', 'resolve', 'vv'];

        $this->runMake(['configs'], directory: self::CONFIGS_DIRECTORY);

        $dist = $this->runMake($resolve, ['VALUE_WIDTH' => '200'], self::CONFIGS_DIRECTORY);

        $this->runMake(['configs', 'local'], directory: self::CONFIGS_DIRECTORY);

        $local = $this->runMake($resolve, ['VALUE_WIDTH' => '200'], self::CONFIGS_DIRECTORY);

        self::assertMatchesRegularExpression('/PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.dist\.php/', $dist);
        self::assertMatchesRegularExpression('/PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.php/', $local);

        $pinned = $this->runMake($resolve, ['VALUE_WIDTH' => '200', 'CONFIG' => 'dist'], self::CONFIGS_DIRECTORY);

        self::assertMatchesRegularExpression('/PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.dist\.php/', $pinned);
    }

    public function testConfigsWritesWhenThereIsNoRepositoryToAsk(): void
    {
        $result = $this->runMake(
            ['configs'],
            ['GIT_DIR' => '/nonexistent'],
            directory: self::CONFIGS_DIRECTORY,
        );

        self::assertStringContainsString('Created', $result);
    }

    public function testConfigsFailsWhenItCannotWrite(): void
    {
        $result = $this->runMake(
            ['configs', '_CONFIGS=/proc/brnshkr/phpstan.php'],
            directory: self::CONFIGS_DIRECTORY,
            doExpectFailure: true,
        );

        self::assertStringContainsString('Error 69', $result);
        self::assertStringNotContainsString('Created /proc', $result);
    }

    public function testArgumentsReachTheTargetTheyFollow(): void
    {
        $alone  = $this->runMake(['consumer-arguments'], directory: self::CONSUMER_DIRECTORY);
        $shared = $this->runMake(
            ['before', 'consumer-arguments', 'own', 'consumer-arguments-second'],
            directory: self::CONSUMER_DIRECTORY,
        );
        $dashed = $this->runMake(['consumer-arguments', '--', '-x'], directory: self::CONSUMER_DIRECTORY);

        self::assertStringContainsString('consumer-arguments [] []', $alone);
        self::assertStringContainsString('consumer-arguments [\'own\' \'before\'] [\'own\']', $shared);
        self::assertStringContainsString('consumer-arguments-second [\'before\'] [\'before\']', $shared);
        self::assertStringContainsString('consumer-arguments [\'-x\'] [\'-x\']', $dashed);
    }

    public function testAnArgumentReachesTheTargetAsWritten(): void
    {
        $result = $this->runMake(
            ['consumer-arguments', '--', '--filter', 'A|B', 'c$HOME'],
            directory: self::CONSUMER_DIRECTORY,
        );

        self::assertStringContainsString('consumer-arguments [\'--filter\' \'A|B\'', $result);
        self::assertStringNotContainsString('cOME', $result);
    }

    public function testAScopeTakingAReservedNameIsRefused(): void
    {
        $result = $this->runMake(['help'], directory: self::RESERVED_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('takes a name that help reserves', $result);
        self::assertStringContainsString('ls', $result);
    }

    public function testUnknownCommandIsReported(): void
    {
        $result = $this->runMake(['no-such-command'], directory: self::CONSUMER_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('Unknown command', $result);
        self::assertStringContainsString('no-such-command', $result);
    }

    public function testCcRemovesTheCachesItIsGivenAndNothingElse(): void
    {
        $this->writeCaches(['first', 'second']);

        $removed = $this->runMake(['cc', 'first'], directory: self::CACHES_DIRECTORY);

        self::assertStringContainsString('Removed ./.cache/first', $removed);
        self::assertDirectoryDoesNotExist(self::CACHES_DIRECTORY . '/.cache/first');
        self::assertDirectoryExists(self::CACHES_DIRECTORY . '/.cache/second');
    }

    public function testCcRefusesACacheNameTheProjectDoesNotHave(): void
    {
        $this->writeCaches(['first']);

        $result = $this->runMake(['cc', 'nope'], directory: self::CACHES_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('No cache named nope', $result);
        self::assertStringContainsString('Try first', $result);
        self::assertDirectoryExists(self::CACHES_DIRECTORY . '/.cache/first');
    }

    public function testCcRemovesEveryCacheWithoutAskingUnderCi(): void
    {
        $this->writeCaches(['first', 'second']);

        $removed = $this->runMake(['cc'], ['CI' => '1'], directory: self::CACHES_DIRECTORY);

        self::assertStringContainsString('This removes every cache', $removed);
        self::assertStringContainsString('Removed ./.cache', $removed);
        self::assertDirectoryDoesNotExist(self::CACHES_DIRECTORY . '/.cache');
    }

    public function testCcSaysSoWhenThereIsNothingToRemove(): void
    {
        self::assertStringContainsString(
            'No caches to remove',
            $this->runMake(['cc'], directory: self::CACHES_DIRECTORY),
        );
    }

    public function testStartupInstallsEachStackThenRunsTheProjectsOwnTargets(): void
    {
        $result = $this->runMake(
            ['startup'],
            ['COMPOSER' => 'echo composer', 'BUN' => 'echo bun'],
            directory: self::STARTUP_DIRECTORY,
        );

        self::assertStringContainsString('composer install', $result);
        self::assertStringContainsString('bun install', $result);
        self::assertStringContainsString("Running startup-own\nstartup-own\n", $result);
    }

    public function testStartupSkipsTheInstallsTheCallerAlreadyRan(): void
    {
        $result = $this->runMake(
            ['startup', '_IS_INSTALLING=1'],
            ['COMPOSER' => 'echo composer', 'BUN' => 'echo bun'],
            directory: self::STARTUP_DIRECTORY,
        );

        self::assertStringNotContainsString('composer install', $result);
        self::assertStringNotContainsString('bun install', $result);
        self::assertStringContainsString("Running startup-own\nstartup-own\n", $result);
    }

    public function testCoverageIsReadFromTheSummaryRatherThanTheLastLineThatMentionsLines(): void
    {
        $reached = $this->runMake(['coverage-reached'], directory: self::COVERAGE_DIRECTORY);
        $missed  = $this->runMake(['coverage-missed'], directory: self::COVERAGE_DIRECTORY, doExpectFailure: true);
        $absent  = $this->runMake(['coverage-absent'], directory: self::COVERAGE_DIRECTORY, doExpectFailure: true);

        self::assertStringNotContainsString('Coverage is below', $reached);
        self::assertStringContainsString('Coverage is below 100%', $missed);
        self::assertStringContainsString('none.txt is missing', $absent);
        self::assertStringNotContainsString('Coverage is below', $absent);
    }

    public function testANameTheProjectDefinesInTwoOfItsOwnFilesIsRefused(): void
    {
        $result = $this->runMake(
            ['twice'],
            directory: __DIR__ . '/Fixtures/Make/Duplicates',
            doExpectFailure: true,
        );

        self::assertStringContainsString('twice is defined in', $result);
        self::assertStringContainsString('again.mk', $result);
        self::assertStringContainsString('defines the same name twice', $result);
    }

    public function testTraceEchoesTheGuardsThatDebugLeavesOut(): void
    {
        $withDebug = $this->runMake(
            ['phpstan'],
            ['DEBUG' => '1'],
            directory: __DIR__ . '/Fixtures/Make/Guard',
            doExpectFailure: true,
        );
        $withTrace = $this->runMake(
            ['phpstan'],
            ['TRACE' => '1'],
            directory: __DIR__ . '/Fixtures/Make/Guard',
            doExpectFailure: true,
        );

        self::assertStringNotContainsString('test -f', $withDebug);
        self::assertStringContainsString('test -f', $withTrace);
    }

    public function testTheErrorCodeIsTheOneTheProjectChose(): void
    {
        $result = $this->runMake(
            ['phpstan'],
            ['BRNSHKR_CONFIG_ERROR_CODE' => '7'],
            directory: __DIR__ . '/Fixtures/Make/Guard',
            doExpectFailure: true,
        );

        self::assertStringContainsString('Error 7', $result);
        self::assertStringNotContainsString('Error 69', $result);
    }

    public function testEditorLinksFollowTheTemplateTheProjectGives(): void
    {
        $result = $this->runMakeHelp(['vvv'], [
            'NO_ANSI'    => '',
            'EDITOR'     => 'vscode',
            'EDITOR_URL' => 'acme://open/{file}#{line}',
        ]);

        self::assertStringContainsString("\033]8;;acme://open/", $result);
        self::assertStringNotContainsString('vscode://file/', $result);
    }

    public function testTheSemverGrammarMatchesWhatSemverAllows(): void
    {
        $resolved = $this->runMakeHelp(['vvv', 'resolve', 'brnshkr.semver'], ['VALUE_WIDTH' => '400']);

        $matched = s($resolved)->match('/SEMVER_REGEX\s+\?=\s+(?<grammar>\S+)/');

        self::assertArrayHasKey('grammar', $matched, $resolved);
        self::assertIsString($matched['grammar']);

        $grammar = sprintf('/^%s$/', $matched['grammar']);

        self::assertNotSame([], s('1.0.0')->match($grammar));
        self::assertNotSame([], s('10.20.30-rc.1')->match($grammar));
        self::assertNotSame([], s('1.0.0-alpha+build.1')->match($grammar));
        self::assertSame([], s('01.0.0')->match($grammar));
        self::assertSame([], s('1.0')->match($grammar));
    }

    public function testFindingsAreCountedByTheIdentifierTheyCarry(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Group';
        $pairs     = $this->runMake(['group-pairs'], directory: $directory);
        $lists     = $this->runMake(['group-lists'], directory: $directory);

        self::assertStringContainsString('2  acme.first   First finding.', $pairs);
        self::assertStringContainsString('1  acme.second  Second finding.', $pairs);
        self::assertStringContainsString('2  acme_second', $lists);
        self::assertStringNotContainsString('acme_second ', $lists);
    }

    public function testFindingsAreCountedByTheCodeACompilerPrints(): void
    {
        $text = $this->runMake(['group-text'], directory: __DIR__ . '/Fixtures/Make/Group');

        self::assertStringContainsString('2  TS2345 ', $text);
        self::assertStringContainsString('1  TS2571 ', $text);
        self::assertStringContainsString('1  MD013/line-length  Line length', $text);
        self::assertStringContainsString('Argument of type \'string\'', $text);
    }

    public function testSuppressedFindingsAreNotCounted(): void
    {
        $eslint = $this->runMake(['group-eslint'], directory: __DIR__ . '/Fixtures/Make/Group');

        self::assertStringContainsString('2  acme/real  Real finding.', $eslint);
        self::assertStringNotContainsString('acme/suppressed', $eslint);
    }

    public function testAMessageContainingBracesKeepsItsText(): void
    {
        $braces = $this->runMake(['group-braces'], directory: __DIR__ . '/Fixtures/Make/Group');

        self::assertStringContainsString('2  acme.shape  Offset \'x\' does not exist on array{a: int}.', $braces);
        self::assertStringContainsString("✘ 2 findings\n", $braces);
    }

    public function testAToolSaysHowManyFindingsItHasOrThatItHasNone(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Group';

        self::assertStringContainsString("✘ 3 findings\n", $this->runMake(['group-pairs'], directory: $directory));
        self::assertStringContainsString("✘ 1 finding\n", $this->runMake(['group-single'], directory: $directory));
        self::assertStringContainsString("✔ No findings\n", $this->runMake(['group-clean'], directory: $directory));
        self::assertStringContainsString(
            "9  acme.frequent  Frequent finding.\n1  acme.rare      Rare finding.\n✘ 10 findings\n",
            $this->runMake(['group-wide'], directory: $directory),
        );
    }

    public function testOutputThatIsNotATerminalGetsNoProgressLine(): void
    {
        $output = $this->runMake(['group-pairs'], directory: __DIR__ . '/Fixtures/Make/Group');

        self::assertStringStartsWith('2  acme.first', $output);
        self::assertStringNotContainsString('Running', $output);
    }

    public function testAGroupIsLabeledOnlyWhenAVerbRunsIt(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Group';
        $verb      = $this->runMake(['groups'], directory: $directory);
        $silenced  = $this->runMake(['groups'], ['ANNOUNCEMENT' => ''], $directory);

        self::assertStringContainsString("[Group] Running group-pairs\n  2  acme.first", $verb);
        self::assertStringNotContainsString('Running…', $verb);
        self::assertStringNotContainsString('[Group]', $silenced);
    }

    public function testParallelGroupsPrintEachReportWhole(): void
    {
        $output = $this->runMake(['-j2', 'groups'], directory: __DIR__ . '/Fixtures/Make/Group');

        self::assertStringContainsString("[Group] Running group-single\n  1  acme.only  Only finding.\n  ✘ 1 finding\n", $output);
        self::assertStringNotContainsString('Running…', $output);

        self::assertStringContainsString(
            "[Group] Running group-pairs\n  2  acme.first   First finding.\n  1  acme.second  Second finding.\n  ✘ 3 findings\n",
            $output,
        );
    }

    public function testOnlyAToolThatReportedNothingFailsTheTarget(): void
    {
        $directory = __DIR__ . '/Fixtures/Make/Group';
        $failing   = $this->runMake(['group-failing'], directory: $directory);
        $crashed   = $this->runMake(['group-crash'], directory: $directory, doExpectFailure: true);

        self::assertStringContainsString("✘ 3 findings\n", $failing);
        self::assertStringNotContainsString('No findings', $crashed);
    }

    public function testEveryPestTargetRefusesWhenPestIsNotInstalled(): void
    {
        foreach (['pest', 'pest-debug', 'pest-list', 'pest-update', 'pest-coverage'] as $target) {
            $result = $this->runMake(
                [$target],
                directory: __DIR__ . '/Fixtures/Make/Guard',
                doExpectFailure: true,
            );

            self::assertStringContainsString('pest is not installed', $result, $target);
        }
    }

    public function testAConfigIsReadFromTheInstalledPackageWhenTheProjectKeepsNone(): void
    {
        $resolved = $this->runMake(
            ['help', 'resolve', 'vv'],
            ['VALUE_WIDTH' => '200'],
            __DIR__ . '/Fixtures/Make/ConfigVendor',
        );

        self::assertMatchesRegularExpression(
            '/PHP_STAN_CONFIG\s+\?=\s+\S+vendor\/brnshkr\/config\/conf\/phpstan\.dist\.php/',
            $resolved,
        );
    }

    public function testTheSearchTakesTheMostSpecificDirectoryThatHasAConfig(): void
    {
        $resolved = $this->runMake(
            ['help', 'resolve', 'vv'],
            ['VALUE_WIDTH' => '200'],
            self::SEARCH_DIRECTORY,
        );

        $expected = [
            'PHP_STAN_CONFIG'     => '\.local\/conf\/php\/phpstan\.php',
            'RECTOR_CONFIG'       => '\.local\/rector\.php',
            'PHP_CS_FIXER_CONFIG' => 'conf\/php\/php-cs-fixer\.php',
        ];

        foreach ($expected as $variable => $path) {
            self::assertMatchesRegularExpression(
                '/' . $variable . '\s+\?=\s+\S+' . $path . '/',
                $resolved,
                $variable,
            );
        }
    }

    public function testAnExampleComesFromTheOtherInstallationWhenThisOneHasNone(): void
    {
        $result = $this->runMake(['configs'], directory: self::FALLBACK_DIRECTORY);

        self::assertStringContainsString('Created', $result);
        self::assertFileExists(self::FALLBACK_DIRECTORY . '/conf/phpstan.dist.php');
    }

    #[Before]
    #[After]
    public function removeWhatTheFixturesWrote(): void
    {
        $written = [
            self::CONFIGS_DIRECTORY . '/.gitignore',
            self::CONFIGS_DIRECTORY . '/conf/php-cs-fixer.dist.php',
            self::CONFIGS_DIRECTORY . '/conf/php-cs-fixer.php',
            self::CONFIGS_DIRECTORY . '/conf/phpstan.dist.php',
            self::CONFIGS_DIRECTORY . '/conf/phpstan.php',
            self::CONFIGS_DIRECTORY . '/conf/phpunit.dist.xml',
            self::CONFIGS_DIRECTORY . '/conf/phpunit.xml',
            self::CONFIGS_DIRECTORY . '/conf/twig-cs-fixer.dist.php',
            self::CONFIGS_DIRECTORY . '/conf/twig-cs-fixer.php',
            self::FALLBACK_DIRECTORY . '/.gitignore',
            self::FALLBACK_DIRECTORY . '/conf/phpstan.dist.php',
            self::FALLBACK_DIRECTORY . '/conf/phpstan.php',
            self::VENDOR_DIRECTORY . '/.gitignore',
            self::VENDOR_DIRECTORY . '/conf/phpstan.dist.php',
            self::VENDOR_DIRECTORY . '/conf/phpstan.php',
            self::STARTUP_DIRECTORY . '/.gitignore',
            self::TSCONFIG_DIRECTORY . '/.gitignore',
            self::TSCONFIG_DIRECTORY . '/tsconfig.json',
            self::TSCONFIG_DIRECTORY . '/conf/tsconfig.json',
        ];

        foreach (self::CONFIG_DIRECTORIES as $directory) {
            $written = [
                ...$written,
                $directory . '/.editorconfig',
                $directory . '/.gitattributes',
                $directory . '/bunfig.toml',
            ];
        }

        foreach ($written as $path) {
            if (is_file($path) || is_link($path)) {
                unlink($path);
            }
        }

        foreach (self::CONFIG_DIRECTORIES as $directory) {
            self::removeDirectory($directory . '/.vscode');
        }

        self::removeDirectory(self::TSCONFIG_DIRECTORY . '/conf');
        self::removeDirectory(self::CACHES_DIRECTORY . '/.cache');
    }

    /**
     * @param list<string> $names
     */
    private function writeCaches(array $names): void
    {
        foreach ($names as $name) {
            mkdir(self::CACHES_DIRECTORY . '/.cache/' . $name, recursive: true);
        }
    }

    private static function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path . '/' . $entry;

            is_dir($child) ? self::removeDirectory($child) : unlink($child);
        }

        rmdir($path);
    }

    private static function assertContainsSymbol(string $symbol, string $output): void
    {
        self::assertMatchesRegularExpression(
            sprintf('/(?<![\w.-])%s(?![\w.-])/', Str::quoteRegex($symbol)),
            $output,
            sprintf('expected %s in output', $symbol),
        );
    }

    private static function assertDoesNotContainSymbol(string $symbol, string $output): void
    {
        self::assertDoesNotMatchRegularExpression(
            sprintf('/(?<![\w.-])%s(?![\w.-])/', Str::quoteRegex($symbol)),
            $output,
            sprintf('unexpected %s in output', $symbol),
        );
    }

    /**
     * @param non-empty-array<string, string> $scenarios
     */
    private function renderScenarios(array $scenarios): string
    {
        $outputGroups = [];

        foreach ($scenarios as $name => $output) {
            $outputHash = md5($output);

            $outputGroups[$outputHash] ??= [
                'output'         => $output,
                'representative' => $name,
                'scenarios'      => [],
            ];

            $outputGroups[$outputHash]['scenarios'][] = $name;
        }

        $baseGroup    = array_first($outputGroups);
        $baseScenario = $baseGroup['representative'];
        $baseOutput   = $baseGroup['output'];
        $manifest     = [];

        foreach ($outputGroups as $group) {
            $manifest[$group['representative']] = $group['scenarios'];
        }

        $rendered = sprintf(
            "=== groups ===\n%s\n\n=== base: %s ===\n%s\n",
            Json::encode($manifest),
            $baseScenario,
            $baseOutput,
        );

        $differ = new Differ(new StrictUnifiedDiffOutputBuilder([
            'addLineNumbers' => false,
            'header'         => '',
        ]));

        foreach ($outputGroups as $outputGroup) {
            if ($outputGroup['representative'] === $baseScenario) {
                continue;
            }

            $diff = $differ->diff($baseOutput, $outputGroup['output']);

            $rendered .= Str::length($diff) < Str::length($outputGroup['output'])
                ? sprintf("=== diff: %s ===\n%s\n", $outputGroup['representative'], $diff)
                : sprintf("=== full: %s ===\n%s\n", $outputGroup['representative'], $outputGroup['output']);
        }

        return $rendered;
    }

    /**
     * @param list<string> $args
     * @param array<string, string> $env
     */
    private function runMakeHelp(array $args = [], array $env = [], bool $doExpectFailure = false): string
    {
        return $this->runMake(['help', ...$args], $env, doExpectFailure: $doExpectFailure);
    }

    /**
     * @param list<string> $args
     * @param array<string, string> $env
     */
    private function runMake(
        array $args = [],
        array $env = [],
        ?string $directory = null,
        bool $doExpectFailure = false,
    ): string {
        $process = new Process([
            'make',
            '--no-print-directory',
            ...($directory === null ? ['-f', self::MAKEFILE_PATH] : []),
            '-C',
            $directory ?? self::FIXTURES_DIRECTORY,
            ...$args,
        ], env: [
            ...array_map(static fn (): false => false, getenv()),
            'HOME' => getenv('HOME'),
            'PATH' => getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
            ...self::BASELINE_ENV,
            ...$env,
        ]);

        $process->run();

        if (!$doExpectFailure && !$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                "make help failed:\n%s\n%s",
                $process->getOutput(),
                $process->getErrorOutput(),
            ));
        }

        return $this->normalizeOutput($process->getOutput() . $process->getErrorOutput());
    }

    private function normalizeOutput(string $output): string
    {
        return s($output)
            ->replaceMatches(sprintf('/%s/', Str::quoteRegex(dirname(self::MAKEFILE_PATH, 2))), '.')
            ->toString()
        ;
    }
}
