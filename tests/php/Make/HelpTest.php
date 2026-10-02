<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Process\ExecutableFinder;

use function array_map;
use function array_unique;
use function count;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal
 */
#[CoversNothing]
final class HelpTest extends TestCase
{
    use MakeTrait;
    use MatchesSnapshots;

    private const string RESERVED_DIRECTORY = __DIR__ . '/../Fixtures/Make/ReservedScope';
    private const int SHELL_ARGUMENT_LIMIT  = 131_072;

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

    public function testHelpOutput(): void
    {
        $this->assertMatchesSnapshot($this->renderScenarios($this->runMakeConcurrently(array_map(
            static fn (array $scenario): array => [
                'args' => ['help', ...$scenario['args'] ?? []],
                'env'  => $scenario['env'] ?? [],
            ],
            self::SNAPSHOT_SCENARIOS,
        ))));
    }

    public function testHelpOutputWithEveryTool(): void
    {
        $this->assertMatchesSnapshot($this->renderScenarios($this->runMakeConcurrently(array_map(
            static fn (array $scenario): array => [
                'args' => ['help', ...$scenario['args'] ?? []],
                'env'  => [
                    ...$scenario['env'] ?? [],
                    'PATH' => self::TOOLS_DIRECTORY . '/bin:' . Str::fromEnvironment('PATH'),
                ],
                'directory' => self::TOOLS_DIRECTORY,
            ],
            self::SNAPSHOT_SCENARIOS,
        ))));
    }

    public function testUnknownScopeReportsError(): void
    {
        $result = $this->runMakeHelp(['nonexistent.scope'], doExpectFailure: true);

        self::assertStringContainsString('Unknown scope', $result);
        self::assertStringContainsString('nonexistent.scope', $result);
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
            self::assertStringNotContainsString(
                $symbol,
                $result,
                sprintf('private symbol %s leaked into output', $symbol),
            );
        }
    }

    public function testEveryAwkImplementationProducesIdenticalOutput(): void
    {
        $implementations = [
            'gawk'         => 'gawk',
            'mawk'         => 'mawk',
            'original-awk' => 'original-awk',
            'busybox awk'  => 'busybox',
        ];

        $outputs          = [];
        $executableFinder = new ExecutableFinder();

        foreach ($implementations as $awk => $binary) {
            if ($executableFinder->find($binary) === null) {
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

            self::assertStringContainsString(
                'from-ifndef-branch',
                $result,
                sprintf('%s: primary branch missing', $label),
            );

            self::assertStringNotContainsString(
                'from-else-ifndef-branch',
                $result,
                sprintf('%s: else branch leaked', $label),
            );
        }
    }

    public function testAWordInsideADefineIsNoTarget(): void
    {
        self::assertStringContainsString(
            'bun \'source\'',
            $this->runMake(['-n', 'bun', '--', 'source'], directory: self::PROJECT_DIRECTORY),
        );
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

    public function testAScopeTakingAReservedNameIsRefused(): void
    {
        $result = $this->runMake(['help'], directory: self::RESERVED_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('takes a name that help reserves', $result);
        self::assertStringContainsString('ls', $result);
    }

    public function testHelpResolvesAnEnvironmentLargerThanOneShellArgument(): void
    {
        $padding = [
            'PADDING_1' => Str::repeat('x', self::SHELL_ARGUMENT_LIMIT / 2),
            'PADDING_2' => Str::repeat('x', self::SHELL_ARGUMENT_LIMIT / 2),
        ];

        self::assertStringContainsString(
            'Available commands:',
            $this->runMake(['help', 'resolve'], $padding, self::PROJECT_DIRECTORY),
        );
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
}
