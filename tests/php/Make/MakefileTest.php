<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Json;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

use function array_diff;
use function array_first;
use function array_key_first;
use function array_map;
use function array_unique;
use function array_values;
use function basename;
use function count;
use function dirname;
use function explode;
use function getenv;
use function implode;
use function mb_substr_count;
use function md5;
use function mkdir;
use function readlink;
use function scandir;
use function shell_exec;
use function sprintf;
use function Symfony\Component\String\s;
use function time;
use function touch;
use function uasort;

/**
 * @internal
 */
#[CoversNothing]
final class MakefileTest extends TestCase
{
    use MakeTrait;
    use MatchesSnapshots;

    private const string CONSUMER_DIRECTORY = __DIR__ . '/../Fixtures/Make/Consumer';
    private const string RESERVED_DIRECTORY = __DIR__ . '/../Fixtures/Make/ReservedScope';
    private const string COVERAGE_DIRECTORY = __DIR__ . '/../Fixtures/Make/Coverage';
    private const string MANIFEST_DIRECTORY = __DIR__ . '/../Fixtures/Make/Manifest';
    private const string PROJECT_DIRECTORY  = __DIR__ . '/../../..';
    private const string SEARCH_DIRECTORY   = __DIR__ . '/../Fixtures/Make/ConfigSearch';
    private const string SPINNER_DIRECTORY  = __DIR__ . '/../Fixtures/Make/Spinner';
    private const string SETTINGS_DIRECTORY = __DIR__ . '/../Fixtures/Make/Settings';
    private const string PACK_DIRECTORY     = __DIR__ . '/../Fixtures/Make/Pack';
    private const string TOOLS_DIRECTORY    = __DIR__ . '/../Fixtures/Make/Tools';

    private const int SHELL_ARGUMENT_LIMIT = 131_072;

    private const array COLORED_ENV = [
        'NO_COLOR'    => '',
        'FORCE_COLOR' => '1',
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
        'theme-brnshkr-explicit' => ['args' => ['vvv'], 'env' => ['THEME' => 'brnshkr', ...self::COLORED_ENV]],
        'theme-symfony'          => ['args' => ['vvv'], 'env' => ['THEME' => 'symfony', ...self::COLORED_ENV]],
        'theme-unknown'          => ['args' => ['vvv'], 'env' => ['THEME' => 'bogus', ...self::COLORED_ENV]],
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

    public function testHelpOutputWithEveryTool(): void
    {
        $scenarios = [];

        foreach (self::SNAPSHOT_SCENARIOS as $name => $scenario) {
            $scenarios[$name] = $this->runMake(
                ['help', ...$scenario['args'] ?? []],
                [
                    ...$scenario['env'] ?? [],
                    'PATH' => self::TOOLS_DIRECTORY . '/bin:' . (getenv('PATH') ?: ''),
                ],
                self::TOOLS_DIRECTORY,
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
        $withColors    = $this->runMakeHelp(env: self::COLORED_ENV);
        $withoutColors = $this->runMakeHelp();

        self::assertStringContainsString("\033[", $withColors);
        self::assertStringNotContainsString("\033[", $withoutColors);
    }

    public function testEditorHyperlinksMatchSelectedEditor(): void
    {
        $vscode = $this->runMakeHelp(['vvv'], ['EDITOR' => 'vscode', ...self::COLORED_ENV]);

        $vscodeWsl = $this->runMakeHelp(['vvv'], [
            'EDITOR'          => 'vscode',
            'WSL_DISTRO_NAME' => 'Ubuntu',
            ...self::COLORED_ENV,
        ]);

        $phpstorm = $this->runMakeHelp(['vvv'], ['EDITOR' => 'phpstorm', ...self::COLORED_ENV]);

        self::assertStringContainsString("\033]8;;vscode://file/", $vscode);
        self::assertStringContainsString("\033]8;;vscode://vscode-remote/wsl+Ubuntu/", $vscodeWsl);
        self::assertStringContainsString("\033]8;;phpstorm://open?file=", $phpstorm);
    }

    public function testOnlyTraceEchoesRecipeLines(): void
    {
        $withTrace    = $this->runMakeHelp(env: ['TRACE' => '1']);
        $withDebug    = $this->runMakeHelp(env: ['DEBUG' => '1']);
        $withoutFlags = $this->runMakeHelp();

        self::assertStringContainsString('_CURDIR=', $withTrace);
        self::assertStringNotContainsString('_CURDIR=', $withDebug);
        self::assertStringNotContainsString('_CURDIR=', $withoutFlags);
    }

    public function testDebugEchoesTheCommandBehindTheSpinnerAndTraceItsWrapper(): void
    {
        $withDebug    = $this->runMake(['spin'], ['DEBUG' => '1'], self::SPINNER_DIRECTORY);
        $withTrace    = $this->runMake(['spin'], ['TRACE' => '1'], self::SPINNER_DIRECTORY);
        $withoutFlags = $this->runMake(['spin'], directory: self::SPINNER_DIRECTORY);

        self::assertStringContainsString("'ran\\n'; exit 0\n", $withDebug);
        self::assertStringNotContainsString('spin_command', $withDebug);
        self::assertStringContainsString('spin_command', $withTrace);
        self::assertSame("ran\n", $withoutFlags);
    }

    public function testEveryAwkImplementationProducesIdenticalOutput(): void
    {
        $implementations = [
            'gawk'         => 'gawk',
            'mawk'         => 'mawk',
            'original-awk' => 'original-awk',
            'busybox awk'  => 'busybox',
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

    public function testDotenvRefusesAStageNameMakeWouldReadAsARule(): void
    {
        touch(self::REFUSED_STAGE_PATH);

        $result = $this->runMake(['dotenv-show'], directory: self::DOTENV_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString(sprintf('`%s` does not name a stage', basename(self::REFUSED_STAGE_PATH)), $result);
    }

    public function testAWordInsideADefineIsNoTarget(): void
    {
        self::assertStringContainsString('bun \'source\'', $this->runMake(['-n', 'bun', '--', 'source'], directory: self::PROJECT_DIRECTORY));
    }

    public function testAProjectNameTheRecipesCannotQuoteIsRefused(): void
    {
        $this->writeProject(self::UNQUOTABLE_DIRECTORY);

        $result = $this->runMake(['help'], directory: self::UNQUOTABLE_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('holds a character the recipes cannot quote', $result);
    }

    public function testAPackageNamedForTheParentDirectoryIsRefused(): void
    {
        $this->writeProject(self::PARENT_NAME_DIRECTORY, '{"name": "vendor/.."}');

        $result = $this->runMake(['help'], directory: self::PARENT_NAME_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('`vendor/..` cannot name a package', $result);
    }

    public function testFixWritesCheckOnlyReadsAndTestOnlyTests(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';
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

    public function testAProjectAddsItsOwnFixerAnalyzerGroupAndTest(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Extras';

        self::assertStringContainsString('own analyzer', $this->runMake(['check'], directory: $directory));
        self::assertStringContainsString('own fixer', $this->runMake(['fix'], directory: $directory));
        self::assertStringContainsString('own group', $this->runMake(['group'], directory: $directory));
        self::assertStringContainsString('own test', $this->runMake(['test'], directory: $directory));
    }

    public function testCiRunsItsTargetsInTheOrderTheyAreListed(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';
        $default   = $this->runMake(['ci'], directory: $directory);
        $fixing    = $this->runMake(['ci'], ['CI_TARGETS' => 'fix check test'], $directory);

        self::assertMatchesRegularExpression('/--dry-run\n.*phpstan analyze.*\n.*phpunit/s', $default);
        self::assertStringNotContainsString(" -v\n", $default);
        self::assertMatchesRegularExpression('/php-cs-fixer fix [^\n]* -v\n.*--dry-run\n.*phpunit/s', $fixing);
    }

    public function testFixRunsRectorBeforePhpCsFixerEvenInParallel(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';

        foreach ([[], ['-j4']] as $flags) {
            $fix = $this->runMake([...$flags, 'fix'], directory: $directory);

            self::assertMatchesRegularExpression('/rector done\n(?:.*\n)*php-cs-fixer fix/', $fix);
            self::assertStringNotContainsString('warning', $fix);
        }
    }

    public function testSnapshotsAreUpdatedThroughTheRunnersOwnMechanism(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';
        $phpunit   = $this->runMake(['phpunit-update'], directory: $directory);
        $pest      = $this->runMake(['phpunit-update'], ['PHP_UNIT' => 'echo pest'], $directory);

        self::assertStringContainsString('update-snapshots --configuration', $phpunit);
        self::assertStringContainsString('--do-not-fail-on-incomplete', $phpunit);
        self::assertStringNotContainsString('-d --update-snapshots', $phpunit);
        self::assertStringContainsString('--update-snapshots --do-not-fail-on-incomplete', $pest);
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

    public function testAVerbAnnouncesEachTargetItRunsAndNothingElseDoes(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';
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
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';
        $reworded  = $this->runMake(['check'], ['ANNOUNCEMENT' => '>>> %s'], $directory);
        $silenced  = $this->runMake(['check'], ['ANNOUNCEMENT' => ''], $directory);

        self::assertStringContainsString("[acme/verbs] >>> phpstan\n", $reworded);
        self::assertStringNotContainsString('[acme/verbs]', $silenced);
    }

    public function testAVerbRunsEveryToolPastAFailureWhileCiAndAFixChainStop(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';
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
        $check = $this->runMake(['-j4', 'check'], directory: __DIR__ . '/../Fixtures/Make/Verbs');

        self::assertMatchesRegularExpression('/Running php-cs-fixer-dry-run\nphp-cs-fixer fix [^\n]*--dry-run\nphp-cs-fixer done/', $check);
        self::assertMatchesRegularExpression('/Running rector-dry-run\nrector process [^\n]*--dry-run\nrector done/', $check);
        self::assertMatchesRegularExpression('/Running phpstan\nphpstan analyze [^\n]*\nphpstan done/', $check);
    }

    public function testATargetLineWrappedBetweenItsNamesKeepsEveryName(): void
    {
        self::assertStringContainsString('wrapped-a/wrapped-b', $this->runMakeHelp());
        self::assertStringContainsString('args=\'foo\'', $this->runMake(['wrapped-a', 'foo']));
    }

    public function testAHelpArgumentIsATargetOfItsOwn(): void
    {
        self::assertSame($this->runMakeHelp(['list-scopes']), $this->runMake(['list-scopes']));
        self::assertSame($this->runMakeHelp(['vvv', 'app.commands']), $this->runMake(['vvv', 'app.commands']));
        self::assertMatchesRegularExpression('/ARGS +=  \'vv\' /', $this->runMake(['r', 'vv']));
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
        $directory = __DIR__ . '/../Fixtures/Make/Collision';

        self::assertStringContainsString('collision', $this->runMake(['fixtures'], directory: $directory));
        self::assertContainsSymbol('brnshkr-fixtures', $this->runMake(['help', 'v'], directory: $directory));
    }

    public function testACollisionInsideAChosenNamespaceIsRefused(): void
    {
        $result = $this->runMake(
            ['shared-fixtures'],
            directory: __DIR__ . '/../Fixtures/Make/PrefixedCollision',
            doExpectFailure: true,
        );

        self::assertStringContainsString('shared-fixtures is defined in', $result);
        self::assertStringContainsString('TARGET_PREFIX', $result);
    }

    public function testATargetInsideAConditionalTheProjectNeverTookIsNotOffered(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Conditionals';

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
        $own    = $this->runMake([], directory: __DIR__ . '/../Fixtures/Make/DefaultGoal');

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
        self::assertMatchesRegularExpression('/^\.PHONY:(?!.* consumer-command ).* help /m', $shared);
        self::assertMatchesRegularExpression('/^\.PHONY:\s*$/m', $none);
    }

    public function testMissingConfigurationNamesThePathAndTheVariable(): void
    {
        $result = $this->runMake(['phpstan'], directory: __DIR__ . '/../Fixtures/Make/Guard', doExpectFailure: true);

        self::assertStringContainsString('conf/phpstan.dist.php is missing', $result);
        self::assertStringContainsString('PHP_STAN_CONFIG', $result);
    }

    public function testGuardedPathIsTheOneOnThisMachineWhenTheToolsRunElsewhere(): void
    {
        $result = $this->runMake(
            ['phpstan', 'APP_DIR=/app', 'PHP_STAN_CONFIG=/app/conf/phpstan.php'],
            directory: __DIR__ . '/../Fixtures/Make/Guard',
            doExpectFailure: true,
        );

        self::assertStringContainsString('./conf/phpstan.php, which PHP_STAN_CONFIG names, is missing', $result);
        self::assertStringNotContainsString('/app/conf/phpstan.php', $result);
    }

    public function testConfigsWritesOnlyWhatTheProjectIsMissing(): void
    {
        $created = $this->runMake(['configs', 'tools'], directory: self::CONFIGS_DIRECTORY);
        $kept    = $this->runMake(['configs', 'tools'], directory: self::CONFIGS_DIRECTORY);

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

    public function testARepositoryWithoutComposerGetsNoExportLines(): void
    {
        $this->runMake(['configs'], directory: self::TSCONFIG_DIRECTORY);

        $gitattributes = new Filesystem()->readFile(self::TSCONFIG_DIRECTORY . '/.gitattributes');

        self::assertStringNotContainsString('export-ignore', $gitattributes);
        self::assertStringEndsWith("linguist-vendored\n###< brnshkr/config ###\n", $gitattributes);
    }

    public function testAComposerProjectWithoutMarkersGetsTheBlockAheadOfItsOwnLines(): void
    {
        new Filesystem()->dumpFile(self::CONFIGS_DIRECTORY . '/.gitattributes', "*.png binary\n");

        $this->runMake(['configs'], directory: self::CONFIGS_DIRECTORY);

        $gitattributes = new Filesystem()->readFile(self::CONFIGS_DIRECTORY . '/.gitattributes');

        self::assertStringStartsWith("###> brnshkr/config ###\n", $gitattributes);
        self::assertMatchesRegularExpression('/^\/composer\.json\s+-export-ignore$/m', $gitattributes);
        self::assertStringNotContainsString('/LICENSE', $gitattributes);
        self::assertStringNotContainsString('/README.md', $gitattributes);
        self::assertStringEndsWith("###< brnshkr/config ###\n\n*.png binary\n", $gitattributes);
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

    public function testConfigsWritesExactlyTheFileItIsNamed(): void
    {
        $this->runMake(['configs', 'phpstan.php'], directory: self::CONFIGS_DIRECTORY);

        self::assertFileEquals(
            self::CONFIGS_DIRECTORY . '/conf/phpstan.php.example',
            self::CONFIGS_DIRECTORY . '/conf/phpstan.php',
        );
        self::assertFileDoesNotExist(self::CONFIGS_DIRECTORY . '/conf/phpstan.dist.php');
        self::assertFileDoesNotExist(self::CONFIGS_DIRECTORY . '/.gitignore');
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
        self::assertFileDoesNotExist(self::VENDOR_DIRECTORY . '/vendor/' . self::getPackageFullName() . '/conf/phpstan.php');
    }

    #[Group('tty')]
    public function testACommandThatExitsItselfRunsOnceBehindTheSpinner(): void
    {
        $output = $this->runMakeOnATty(['spin'], self::SPINNER_DIRECTORY);

        self::assertSame(1, mb_substr_count($output, 'ran'));
    }

    #[Group('tty')]
    public function testAnInterruptStopsTheCommandBehindTheSpinnerWithoutRerunningIt(): void
    {
        $inputStream = new InputStream();

        $process = new Process([
            'script',
            '-qfc',
            sprintf('make --no-print-directory -C %s spin-until-interrupted', self::SPINNER_DIRECTORY),
            '/dev/null',
        ], env: [
            ...self::getBaselineEnvironment(),
            'NO_COLOR' => '',
        ], input: $inputStream, timeout: 20);

        $process->start();
        $process->waitUntil(static fn (string $type, string $output): bool => Str::contains($output, 'started'));
        $inputStream->write("\x03");
        $inputStream->close();
        $process->wait();

        self::assertSame(1, mb_substr_count($process->getOutput(), 'started'));
    }

    public function testAManifestOpeningOnTheNameLineStillNamesThePackage(): void
    {
        $resolved = $this->runMake(['help', 'resolve', 'vv'], directory: self::MANIFEST_DIRECTORY);

        self::assertMatchesRegularExpression('/PACKAGE\s+\?=\s+one-line-manifest/', $resolved);
        self::assertMatchesRegularExpression('/VENDOR\s+\?=\s+@acme/', $resolved);
    }

    public function testTheListTargetsNameWhatEachPackageShips(): void
    {
        $composer = $this->runMake(['composer-list'], directory: self::PACK_DIRECTORY);
        $bun      = $this->runMake(['bun-list'], directory: self::PACK_DIRECTORY);

        self::assertStringContainsString('src/Example.php', $composer);
        self::assertStringNotContainsString('tests/Example.php', $composer);
        self::assertStringContainsString('src/index.mjs', $bun);
        self::assertStringNotContainsString('tests/', $bun);
    }

    #[Group('build')]
    public function testTheFilesEachPackageShips(): void
    {
        $composer = $this->runMake(['composer-list'], directory: self::PROJECT_DIRECTORY);
        $bun      = $this->runMake(['bun-list'], directory: self::PROJECT_DIRECTORY);

        $this->assertMatchesSnapshot(sprintf(
            "=== composer ===\n%s\n\n=== bun ===\n%s\n",
            Str::trim($composer),
            Str::trim($bun),
        ));
    }

    public function testThePackageFallbackReadsTheTrackedHalfOnly(): void
    {
        $resolved = $this->runMake(['help', 'resolve', 'vv'], ['VALUE_WIDTH' => '200'], self::VENDOR_DIRECTORY);

        self::assertMatchesRegularExpression(self::getInstalledPhpStanConfigPattern(), $resolved);
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

    public function testAnOptionWithAValueReachesTheTargetItFollows(): void
    {
        $result = $this->runMake(
            ['--', '--shared=1', 'consumer-arguments', '--own=2', 'consumer-arguments-second', '--second=$HOME'],
            directory: self::CONSUMER_DIRECTORY,
        );

        self::assertStringContainsString('consumer-arguments [\'--own=2\' \'--shared=1\']', $result);
        self::assertStringContainsString('consumer-arguments-second [\'--second=', $result);
        self::assertStringContainsString('\' \'--shared=1\'] [\'--second=', $result);
        self::assertStringNotContainsString('--second=OME', $result);
    }

    public function testAnOptionWithAValueIsWeighedAsAnArgument(): void
    {
        $this->writeCaches(['first']);

        $result = $this->runMake(['cc', '--', '--first=x'], directory: self::CACHES_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('No cache named first=x', $result);
        self::assertDirectoryExists(self::CACHES_DIRECTORY . '/.cache/first');
    }

    public function testAFileArgumentRunsWithoutMakeReportingIt(): void
    {
        $result = $this->runMake(['consumer-arguments', '.', '../Consumer'], directory: self::CONSUMER_DIRECTORY);

        self::assertStringContainsString('consumer-arguments [\'.\' \'../Consumer\']', $result);
        self::assertStringNotContainsString('is up to date', $result);
    }

    public function testASubcommandTheArgumentsNameReplacesTheTargetsOwn(): void
    {
        $environment = ['PATH' => self::TOOLS_DIRECTORY . '/bin:' . (getenv('PATH') ?: '')];

        $phpStan = $this->runMake(
            ['-n', 'phpstan', '_COMMAND=', '--', 'clear-result-cache', '--level', '5', 'src', '--error-format=json'],
            $environment,
            self::TOOLS_DIRECTORY,
        );

        $rector = $this->runMake(
            ['-n', 'rector', '_COMMAND=', '--', 'process', '--dry-run', '--output-format=json', 'src'],
            $environment,
            self::TOOLS_DIRECTORY,
        );

        $ownLevel = $this->runMake(['-n', 'phpstan', '--', '--level', '5'], $environment, self::TOOLS_DIRECTORY);

        self::assertStringContainsString('\'clear-result-cache\' \'src\' \'--error-format=json\' --configuration', $phpStan);
        self::assertStringNotContainsString('analyze', $phpStan);
        self::assertStringContainsString('\'process\' \'--output-format=json\' \'src\' --config', $rector);
        self::assertStringNotContainsString('rector process', $rector);
        self::assertStringContainsString('--dry-run', $rector);
        self::assertStringContainsString('phpstan analyze \'--level\' \'5\' --configuration', $ownLevel);
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
        self::assertStringContainsString('to see available commands', $result);
    }

    public function testCcRemovesTheCachesItIsGivenAndNothingElse(): void
    {
        $this->writeCaches(['first', 'second']);

        $removed = $this->runMake(['cc', 'first'], directory: self::CACHES_DIRECTORY);

        self::assertStringContainsString('Removed ./.cache/first', $removed);
        self::assertDirectoryDoesNotExist(self::CACHES_DIRECTORY . '/.cache/first');
        self::assertDirectoryExists(self::CACHES_DIRECTORY . '/.cache/second');
    }

    public function testCcTakesAToolNameAndNeverRunsTheTargetItNames(): void
    {
        $this->writeCaches(['help.cache', 'other']);

        $removed = $this->runMake(['cc', 'help'], directory: self::CACHES_DIRECTORY);

        self::assertStringContainsString('Removed ./.cache/help.cache', $removed);
        self::assertStringNotContainsString('Usage:', $removed);
        self::assertDirectoryExists(self::CACHES_DIRECTORY . '/.cache/other');
    }

    public function testAProjectTargetAfterANamingVerbIsRefusedBeforeAnythingRuns(): void
    {
        $result = $this->runMake(
            ['cc', 'fixtures'],
            directory: __DIR__ . '/../Fixtures/Make/Collision',
            doExpectFailure: true,
        );

        self::assertStringContainsString('fixtures is a target of this project, so it cannot follow cc', $result);
        self::assertDoesNotMatchRegularExpression('/^collision$/m', $result);
    }

    public function testCcRefusesACacheNameTheProjectDoesNotHave(): void
    {
        $this->writeCaches(['first']);

        $result = $this->runMake(['cc', 'nope'], directory: self::CACHES_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('No cache named nope', $result);
        self::assertStringContainsString('Try first', $result);
        self::assertDirectoryExists(self::CACHES_DIRECTORY . '/.cache/first');
    }

    public function testAMistypedArgumentIsScoredAgainstWhatTheTargetAccepts(): void
    {
        $this->writeCaches(['phpstan.cache']);

        $result = $this->runMake(['cc', 'phpstna.cache'], directory: self::CACHES_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('No cache named phpstna.cache', $result);
        self::assertStringContainsString('Did you mean phpstan.cache', $result);
        self::assertStringNotContainsString('Try ', $result);
        self::assertStringNotContainsString('Removed', $result);
        self::assertDirectoryExists(self::CACHES_DIRECTORY . '/.cache/phpstan.cache');
    }

    public function testASuggestionOneLetterOffStandsAlone(): void
    {
        $this->writeCaches(['eslint', 'eslint-group', 'eslint-print']);

        $result = $this->runMake(['cc', 'eslin'], directory: self::CACHES_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('Did you mean eslint?', $result);
        self::assertStringNotContainsString('eslint-group', $result);
        self::assertStringNotContainsString('eslint-print', $result);
    }

    public function testCcNamesACacheWithoutRunningItsName(): void
    {
        $this->writeCaches([
            '$(id)',
            'it\'s',
            "x\e]0;title\x07y",
        ]);

        $listed  = $this->runMake(['cc'], directory: self::CACHES_DIRECTORY);
        $refused = $this->runMake(['cc', 'nope'], directory: self::CACHES_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('$(id), it\'s or x?]0;title?y', $listed);
        self::assertStringContainsString('Try $(id), it\'s or x?]0;title?y', $refused);
    }

    public function testALinkedPathIsCheckedWithoutRunningIt(): void
    {
        $this->writeCaches(['x;touch${IFS}hyperlink-ran;.d']);

        $this->runMake(['cc', 'x;touch${IFS}hyperlink-ran;.d'], [
            'EDITOR' => 'vscode',
            ...self::COLORED_ENV,
        ], self::CACHES_DIRECTORY);

        self::assertFileDoesNotExist(self::CACHES_DIRECTORY . '/hyperlink-ran');
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

        $plainOutput                 = $outputOfEachForm[0];
        $outputWithPrivateExports    = $this->runMake(['help', 'env', 'vvv'], directory: self::DOTENV_DIRECTORY);
        $outputWithACommandLineValue = $this->runMake(['help', 'env', 'DOTENV_FIXTURE_LATER=typed'], directory: self::DOTENV_DIRECTORY);

        $outputWithEditorLinks = $this->runMake(
            ['help', 'env'],
            ['EDITOR' => 'vscode', 'EDITOR_URL' => 'acme://open/{file}#{line}', ...self::COLORED_ENV],
            self::DOTENV_DIRECTORY,
        );

        [$exportedSection, $environmentFileSection] = explode('Environment files:', $plainOutput, 2) + ['', ''];

        self::assertCount(1, array_unique($outputOfEachForm));
        self::assertMatchesRegularExpression('/^Exported:$/m', $exportedSection);
        self::assertMatchesRegularExpression('/^\s+FIXTURE_MAKEFILE_EXPORT\s+exported by the makefile$/m', $exportedSection);
        self::assertStringNotContainsString('DOTENV_FIXTURE_', $exportedSection);
        self::assertStringNotContainsString('PWD', $exportedSection);
        self::assertStringNotContainsString('_HAS_DOCKER', $plainOutput);
        self::assertStringContainsString('_HAS_DOCKER', $outputWithPrivateExports);
        self::assertMatchesRegularExpression('/^\s+\.\/\.env\n\s+DOTENV_FIXTURE_PLAIN\s+one\s+\(replaced by the environment\)$/m', $environmentFileSection);
        self::assertMatchesRegularExpression('/^\s+DOTENV_FIXTURE_LAYER\s+base\s+\(replaced by \.\/\.env\.dev\.local\)$/m', $environmentFileSection);
        self::assertMatchesRegularExpression('/^\s+\.\/\.env\.dev\.local\n\s+DOTENV_FIXTURE_LAYER\s+dev-local$/m', $environmentFileSection);
        self::assertMatchesRegularExpression('/^\s+DOTENV_FIXTURE_LATER\s+later\s+\(replaced by the command line\)$/m', $outputWithACommandLineValue);
        self::assertStringContainsString("acme://open/Makefile#13\e\\FIXTURE_MAKEFILE_EXPORT", $outputWithEditorLinks);
        self::assertStringContainsString("acme://open/.env#4\e\\DOTENV_FIXTURE_PLAIN", $outputWithEditorLinks);
    }

    #[Group('tty')]
    public function testAConfirmationWhoseInputIsNoTerminalTakesTheDefault(): void
    {
        $this->writeCaches(['first']);

        $this->runMakeOnATty(['cc', '</dev/null'], self::CACHES_DIRECTORY, "y\n");

        self::assertDirectoryExists(self::CACHES_DIRECTORY . '/.cache/first');
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

    public function testHelpResolvesAnEnvironmentLargerThanOneShellArgument(): void
    {
        $padding = [
            'PADDING_1' => Str::repeat('x', self::SHELL_ARGUMENT_LIMIT / 2),
            'PADDING_2' => Str::repeat('x', self::SHELL_ARGUMENT_LIMIT / 2),
        ];

        self::assertStringContainsString('Available commands:', $this->runMake(['help', 'resolve'], $padding, self::PROJECT_DIRECTORY));
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

    public function testAMistypedConfigNameIsScoredTheSameWay(): void
    {
        $result = $this->runMake(['configs', 'phpstna'], directory: self::CONFIGS_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('No config named phpstna', $result);
        self::assertStringContainsString('Did you mean phpstan', $result);
    }

    public function testCcKeepsEveryCacheWhereNobodyCanAnswer(): void
    {
        $this->writeCaches(['first', 'second']);

        $kept = $this->runMake(['cc'], ['CI' => '1'], directory: self::CACHES_DIRECTORY);

        self::assertStringContainsString('This removes every cache', $kept);
        self::assertStringContainsString('Nothing removed', $kept);
        self::assertDirectoryExists(self::CACHES_DIRECTORY . '/.cache');
    }

    public function testCcSaysSoWhenThereIsNothingToRemove(): void
    {
        self::assertStringContainsString(
            'No caches to remove',
            $this->runMake(['cc'], directory: self::CACHES_DIRECTORY),
        );
    }

    public function testCcReportsNoRemovalForACacheThatWasNeverThere(): void
    {
        $result = $this->runMake(['cc', 'nope'], directory: self::CACHES_DIRECTORY);

        self::assertStringContainsString('No caches to remove', $result);
        self::assertStringNotContainsString('Removed', $result);
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
        $metric  = $this->runMake(['coverage-metric'], directory: self::COVERAGE_DIRECTORY, doExpectFailure: true);

        self::assertStringNotContainsString('coverage is below', $reached);
        self::assertStringContainsString('coverage is below', $missed);
        self::assertStringContainsString('100%', $missed);
        self::assertStringContainsString('none.txt is missing', $absent);
        self::assertStringNotContainsString('coverage is below', $absent);
        self::assertStringContainsString('Methods', $metric);
        self::assertStringContainsString('95%', $metric);
    }

    public function testANameTheProjectDefinesInTwoOfItsOwnFilesIsRefused(): void
    {
        $result = $this->runMake(
            ['twice'],
            directory: __DIR__ . '/../Fixtures/Make/Duplicates',
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
            directory: __DIR__ . '/../Fixtures/Make/Guard',
            doExpectFailure: true,
        );
        $withTrace = $this->runMake(
            ['phpstan'],
            ['TRACE' => '1'],
            directory: __DIR__ . '/../Fixtures/Make/Guard',
            doExpectFailure: true,
        );

        self::assertStringNotContainsString('test -r', $withDebug);
        self::assertStringContainsString('test -r', $withTrace);
    }

    public function testTheErrorCodeIsTheOneTheProjectChose(): void
    {
        $result = $this->runMake(
            ['phpstan'],
            ['BRNSHKR_CONFIG_ERROR_CODE' => '7'],
            directory: __DIR__ . '/../Fixtures/Make/Guard',
            doExpectFailure: true,
        );

        self::assertStringContainsString('Error 7', $result);
        self::assertStringNotContainsString('Error 69', $result);
    }

    public function testEditorLinksFollowTheTemplateTheProjectGives(): void
    {
        $result = $this->runMakeHelp(['vvv'], [
            'EDITOR'     => 'vscode',
            'EDITOR_URL' => 'acme://open/{file}#{line}',
            ...self::COLORED_ENV,
        ]);

        self::assertStringContainsString("\033]8;;acme://open/", $result);
        self::assertStringNotContainsString('vscode://file/', $result);
    }

    public function testAPrintTargetThatNeedsAFileSaysSoItself(): void
    {
        $eslint    = $this->runMake(['eslint-print'], directory: self::PROJECT_DIRECTORY, doExpectFailure: true);
        $stylelint = $this->runMake(['stylelint-print'], directory: self::PROJECT_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('eslint-print needs a file, pass one as an argument.', $eslint);
        self::assertStringContainsString('stylelint-print needs a file, pass one as an argument.', $stylelint);
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

    public function testEditorLinksNameTheProjectDirectoryOnce(): void
    {
        $helpDirectory = self::getRealPath(self::FIXTURES_DIRECTORY);

        $result = $this->runMakeHelp(['vvv'], [
            'EDITOR_URL' => 'acme://open/{cwd}/{file}#{line}',
            ...self::COLORED_ENV,
        ]);

        self::assertStringContainsString('acme://open/' . $helpDirectory . '/.local/Makefile#', $result);
        self::assertStringNotContainsString($helpDirectory . '/' . $helpDirectory, $result);
    }

    public function testALoggedPathLinksRelativeToTheProject(): void
    {
        $configsDirectory = self::getRealPath(self::CONFIGS_DIRECTORY);

        $result = $this->runMake(['configs'], [
            'EDITOR_URL' => 'acme://open/{cwd}/{file}#{line}',
            ...self::COLORED_ENV,
        ], directory: self::CONFIGS_DIRECTORY);

        self::assertStringContainsString('acme://open/' . $configsDirectory . '/.gitignore#', $result);
        self::assertStringNotContainsString($configsDirectory . '/' . $configsDirectory, $result);
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
        $directory = __DIR__ . '/../Fixtures/Make/Group';
        $pairs     = $this->runMake(['group-pairs'], directory: $directory);
        $lists     = $this->runMake(['group-lists'], directory: $directory);

        self::assertStringContainsString('2  acme.first   First finding.', $pairs);
        self::assertStringContainsString('1  acme.second  Second finding.', $pairs);
        self::assertStringContainsString('2  acme_second', $lists);
        self::assertStringNotContainsString('acme_second ', $lists);
    }

    public function testFindingsAreCountedByTheCodeACompilerPrints(): void
    {
        $text = $this->runMake(['group-text'], directory: __DIR__ . '/../Fixtures/Make/Group');

        self::assertStringContainsString('2  TS2345 ', $text);
        self::assertStringContainsString('1  TS2571 ', $text);
        self::assertStringContainsString('1  MD013/line-length  Line length', $text);
        self::assertStringContainsString('Argument of type \'string\'', $text);
    }

    public function testSuppressedFindingsAreNotCounted(): void
    {
        $eslint = $this->runMake(['group-eslint'], directory: __DIR__ . '/../Fixtures/Make/Group');

        self::assertStringContainsString('2  acme/real  Real finding.', $eslint);
        self::assertStringNotContainsString('acme/suppressed', $eslint);
    }

    public function testAMessageContainingBracesKeepsItsText(): void
    {
        $braces = $this->runMake(['group-braces'], directory: __DIR__ . '/../Fixtures/Make/Group');

        self::assertStringContainsString('2  acme.shape  Offset \'x\' does not exist on array{a: int}.', $braces);
        self::assertStringContainsString("✘ 2 findings\n", $braces);
    }

    public function testAToolSaysHowManyFindingsItHasOrThatItHasNone(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Group';

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
        $output = $this->runMake(['group-pairs'], directory: __DIR__ . '/../Fixtures/Make/Group');

        self::assertStringStartsWith('2  acme.first', $output);
        self::assertStringNotContainsString('Running', $output);
    }

    public function testAGroupIsLabeledOnlyWhenAVerbRunsIt(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Group';
        $verb      = $this->runMake(['groups'], directory: $directory);
        $silenced  = $this->runMake(['groups'], ['ANNOUNCEMENT' => ''], $directory);

        self::assertStringContainsString("[Group] Running group-pairs\n  2  acme.first", $verb);
        self::assertStringNotContainsString('Running…', $verb);
        self::assertStringNotContainsString('[Group]', $silenced);
    }

    public function testParallelGroupsPrintEachReportWhole(): void
    {
        $output = $this->runMake(['-j2', 'groups'], directory: __DIR__ . '/../Fixtures/Make/Group');

        self::assertStringContainsString("[Group] Running group-single\n  1  acme.only  Only finding.\n  ✘ 1 finding\n", $output);
        self::assertStringNotContainsString('Running…', $output);

        self::assertStringContainsString(
            "[Group] Running group-pairs\n  2  acme.first   First finding.\n  1  acme.second  Second finding.\n  ✘ 3 findings\n",
            $output,
        );
    }

    public function testOnlyAToolThatReportedNothingFailsTheTarget(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Group';
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
                directory: __DIR__ . '/../Fixtures/Make/Guard',
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
            __DIR__ . '/../Fixtures/Make/ConfigVendor',
        );

        self::assertMatchesRegularExpression(self::getInstalledPhpStanConfigPattern(), $resolved);
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
        $result = $this->runMake(['configs', 'tools'], directory: self::FALLBACK_DIRECTORY);

        self::assertStringContainsString('Created', $result);
        self::assertFileExists(self::FALLBACK_DIRECTORY . '/conf/phpstan.dist.php');
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
        $result = $this->runMake(['changelog', 'GIT=printf \'%s\n\' \'v1;false\''], directory: self::CONSUMER_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('`v1;false` does not name a tag', $result);
    }

    public function testAnArgumentIsWeighedWithoutRunningIt(): void
    {
        $this->runLinter(['hadolint', '--', '$(touch${IFS}positional-ran)'], doExpectFailure: true);

        self::assertFileDoesNotExist(self::LINTERS_DIRECTORY . '/positional-ran');
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
        $cachedPath = self::LINTERS_DIRECTORY . '/.cache/semgrep/default.json';

        new Filesystem()->dumpFile($cachedPath, '{"rules":[{"id":"old"}]}');
        touch($cachedPath, time() - 8 * 86_400);

        $output = $this->runLinter(['semgrep', 'SEMGREP_RULESETS=default', 'SEMGREP_CONFIG=conf/shipped.yaml']);

        self::assertStringContainsString('The default ruleset changed since it was last fetched.', $output);
        self::assertSame(['default.json', 'rules.json'], array_values(array_diff(scandir(dirname($cachedPath)) ?: [], ['.', '..'])));
    }

    private function writeProject(string $directory, ?string $manifest = null): void
    {
        $filesystem = new Filesystem();

        $filesystem->dumpFile($directory . '/Makefile', "include ../../../../../conf/Makefile\n");

        if ($manifest !== null) {
            $filesystem->dumpFile($directory . '/package.json', $manifest);
        }
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

    private static function getPackageFullName(): string
    {
        return ComposerJson::forThisLibrary()->getPackageFullName() ?? self::fail('composer.json names no package.');
    }

    private static function getInstalledPhpStanConfigPattern(): string
    {
        return sprintf(
            '/PHP_STAN_CONFIG\s+\?=\s+\S+vendor\/%s\/conf\/phpstan\.dist\.php/',
            Str::quoteRegex(self::getPackageFullName()),
        );
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

        $renderedOutputs = [$baseScenario => $baseOutput];

        foreach ($outputGroups as $outputGroup) {
            $name   = $outputGroup['representative'];
            $output = $outputGroup['output'];

            if ($name === $baseScenario) {
                continue;
            }

            $diffs = array_map(
                static fn (string $reference): string => $differ->diff($reference, $output),
                $renderedOutputs,
            );

            uasort(
                $diffs,
                static fn (string $first, string $second): int => Str::length($first) <=> Str::length($second),
            );

            $closest = array_key_first($diffs);

            $rendered .= Str::length($diffs[$closest]) < Str::length($output)
                ? sprintf("=== diff: %s from %s ===\n%s\n", $name, $closest, $diffs[$closest])
                : sprintf("=== full: %s ===\n%s\n", $name, $output);

            $renderedOutputs[$name] = $output;
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
     */
    private function runLinter(array $args, bool $doExpectFailure = false): string
    {
        return $this->runMake(
            $args,
            ['PATH' => self::LINTERS_DIRECTORY . '/bin:' . (getenv('PATH') ?: '')],
            self::LINTERS_DIRECTORY,
            $doExpectFailure,
        );
    }

    /**
     * @param list<string> $args
     * @param array<string, false|string> $env
     */
    private function runMakeOnATty(
        array $args,
        string $directory,
        ?string $input = null,
        array $env = [],
    ): string {
        $process = new Process([
            'script',
            '-qfc',
            sprintf('make --no-print-directory -C %s %s', $directory, implode(' ', $args)),
            '/dev/null',
        ], env: [
            ...self::getBaselineEnvironment(),
            'NO_COLOR' => '',
            ...$env,
        ]);

        if ($input !== null) {
            $process->setInput($input);
        }

        $process->mustRun();

        return $process->getOutput();
    }
}
