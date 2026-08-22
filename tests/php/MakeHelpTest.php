<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Json;
use Brnshkr\Config\Str;
use JsonException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Process\Process;

use function array_first;
use function dirname;
use function md5;
use function shell_exec;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal
 */
#[CoversNothing]
final class MakeHelpTest extends TestCase
{
    use MatchesSnapshots;

    private const string MAKEFILE_PATH      = __DIR__ . '/../../conf/Makefile';
    private const string FIXTURES_DIRECTORY = __DIR__ . '/Fixtures/Make';

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
        'list-scopes-alias'      => ['args' => ['ls']],
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
     * @throws JsonException
     * @throws LogicException
     * @throws RuntimeException
     */
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

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    public function testUnknownScopeReportsError(): void
    {
        $result = $this->runMakeHelp(['nonexistent.scope'], doExpectFailure: true);

        self::assertStringContainsString('Unknown scope', $result);
        self::assertStringContainsString('nonexistent.scope', $result);
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    public function testUnknownEditorReportsError(): void
    {
        $result = $this->runMakeHelp(env: ['EDITOR' => 'nano'], doExpectFailure: true);

        self::assertStringContainsString('Unknown editor', $result);
        self::assertStringContainsString('"nano"', $result);
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    public function testAutoIncludeCoversEverySupportedDirectory(): void
    {
        $result = $this->runMakeHelp(['vvv']);

        foreach ([
            'root-makefile-command',
            'root-mk-command',
            'make-makefile-command',
            'make-mk-command',
            'conf-makefile-command',
            'conf-mk-command',
            'conf-make-makefile-command',
            'conf-make-mk-command',
            'local-makefile-command',
            'local-mk-command',
            'local-make-makefile-command',
            'local-make-mk-command',
        ] as $symbol) {
            self::assertStringContainsString($symbol, $result, sprintf('expected %s in output', $symbol));
        }
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    public function testFilesOutsideAutoIncludePathsAreNotIncluded(): void
    {
        $result = $this->runMakeHelp(['vvv']);

        foreach ([
            'not-included-command',
            'excluded-makefile-command',
            'excluded-mk-command',
        ] as $symbol) {
            self::assertStringNotContainsString($symbol, $result, sprintf('unexpected %s in output', $symbol));
        }
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
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

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    public function testAnsiEscapesAreEmittedWhenColorsAreEnabled(): void
    {
        $withColors    = $this->runMakeHelp(env: ['NO_ANSI' => '']);
        $withoutColors = $this->runMakeHelp();

        self::assertStringContainsString("\033[", $withColors);
        self::assertStringNotContainsString("\033[", $withoutColors);
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    public function testEditorHyperlinksMatchSelectedEditor(): void
    {
        $vscode    = $this->runMakeHelp(['vvv'], ['NO_ANSI' => '', 'EDITOR' => 'vscode']);
        $vscodeWsl = $this->runMakeHelp(['vvv'], ['NO_ANSI' => '', 'EDITOR' => 'vscode', 'WSL_DISTRO_NAME' => 'Ubuntu']);
        $phpstorm  = $this->runMakeHelp(['vvv'], ['NO_ANSI' => '', 'EDITOR' => 'phpstorm']);

        self::assertStringContainsString("\033]8;;vscode://file/", $vscode);
        self::assertStringContainsString("\033]8;;vscode://vscode-remote/wsl+Ubuntu/", $vscodeWsl);
        self::assertStringContainsString("\033]8;;phpstorm://open?file=", $phpstorm);
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    public function testDebugFlagEchoesRecipeLines(): void
    {
        $withDebug    = $this->runMakeHelp(env: ['DEBUG' => '1']);
        $withoutDebug = $this->runMakeHelp();

        self::assertStringContainsString('_PWD=', $withDebug);
        self::assertStringNotContainsString('_PWD=', $withoutDebug);
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    public function testMawkAndGawkProduceIdenticalOutput(): void
    {
        if (shell_exec('command -v mawk') === null || shell_exec('command -v gawk') === null) {
            self::markTestSkipped('mawk or gawk not available on this system');
        }

        $gawk = $this->runMakeHelp(['vvv'], ['AWK' => 'gawk']);
        $mawk = $this->runMakeHelp(['vvv'], ['AWK' => 'mawk']);

        self::assertSame($gawk, $mawk);
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
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

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
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

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
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

    /**
     * @param non-empty-array<string, string> $scenarios
     *
     * @throws JsonException
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

        $differ = new Differ(new UnifiedDiffOutputBuilder('', false));

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
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    private function runMakeHelp(array $args = [], array $env = [], bool $doExpectFailure = false): string
    {
        $process = new Process([
            'make',
            '--no-print-directory',
            '-f',
            self::MAKEFILE_PATH,
            '-C',
            self::FIXTURES_DIRECTORY,
            'help',
            ...$args,
        ], env: [
            'HOME' => $_SERVER['HOME'] ?? '',
            'PATH' => $_SERVER['PATH'] ?? '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
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
