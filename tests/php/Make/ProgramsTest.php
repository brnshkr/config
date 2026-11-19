<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\ContainerTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function array_filter;
use function array_values;
use function count;
use function sprintf;

/**
 * @internal
 */
#[CoversNothing]
final class ProgramsTest extends TestCase
{
    use ContainerTrait;

    private const string PYTHON_PROGRAM_VARIABLE    = '_SEMGREP_RULES_PROGRAM_SOURCE';
    private const string PHP_PROGRAM_SUFFIX         = '_PROGRAM_SOURCE';
    private const string PHP_OPENING_TAG            = "<?php\n\n";
    private const string PHPSTAN_CONFIG_PATH        = __DIR__ . '/../Fixtures/Make/Programs/phpstan.php';
    private const string AWK_PARSE_ONLY_PROGRAM     = 'BEGIN { exit } END { exit }';
    private const string AWK_LOCAL_VARIABLE_WARNING = 'shadows global variable';
    private const string DUMP_GOAL                  = '_brnshkr_dump';

    private const array DRY_RUN_ARTIFACT_CODES = [
        'SC2041',
        'SC2043',
        'SC2066',
    ];

    #[DataProvider('provideEveryEmbeddedProgramParsesCases')]
    #[Group('make4')]
    public function testEveryEmbeddedProgramParses(string $variableName): void
    {
        $programPath  = $this->createFixtureRoot() . '/program';
        $isPhpProgram = Str::endsWith($variableName, self::PHP_PROGRAM_SUFFIX);

        new Filesystem()->dumpFile(
            $programPath,
            ($isPhpProgram ? self::PHP_OPENING_TAG : '') . $this->dumpVariable($variableName) . "\n",
        );

        $command = $isPhpProgram
            ? [
                'php',
                '-l',
                $programPath,
            ]
            : [
                'gawk',
                '--posix',
                '--lint=invalid',
                '-e',
                self::AWK_PARSE_ONLY_PROGRAM,
                '-f',
                $programPath,
                '/dev/null',
            ];

        $process = new Process($command);

        $process->run();

        $warnings = array_filter(
            Str::split(Str::trim($process->getErrorOutput()), "\n"),
            static fn (string $warning): bool => $warning !== ''
                && !Str::contains($warning, self::AWK_LOCAL_VARIABLE_WARNING),
        );

        self::assertTrue($process->isSuccessful(), $process->getOutput() . $process->getErrorOutput());
        self::assertSame([], array_values($warnings));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEveryEmbeddedProgramParsesCases(): iterable
    {
        $makefile           = new Filesystem()->readFile(self::MAKEFILE_PATH);
        $programDefinitions = Str::matchAll($makefile, '/^define (?<name>_\w+_(?:AWK|AWK_SOURCE|PROGRAM_SOURCE))$/m');

        foreach ($programDefinitions as $programDefinition) {
            $variableName = $programDefinition['name'] ?? '';
            $isInlined    = Str::contains($makefile, "\n$({$variableName})\n");

            if ($variableName !== self::PYTHON_PROGRAM_VARIABLE && !$isInlined) {
                yield $variableName => [$variableName];
            }
        }
    }

    #[Group('make4')]
    public function testEveryPhpProgramPassesPhpStan(): void
    {
        self::assertStringContainsString('No errors', $this->runOverEveryPhpProgram('phpstan'));
    }

    #[Group('make4')]
    public function testEveryPhpProgramPassesPhpCsFixer(): void
    {
        self::assertStringContainsString(
            sprintf('Found 0 of %d files', count(self::getPhpProgramVariableNames())),
            $this->runOverEveryPhpProgram('php-cs-fixer-dry-run'),
        );
    }

    #[Group('make4')]
    public function testEveryPhpProgramPassesRector(): void
    {
        self::assertStringContainsString('[OK] Rector is done!', $this->runOverEveryPhpProgram('rector-dry-run'));
    }

    #[Group('container')]
    #[Group('make4')]
    #[Group('semgrep')]
    public function testThePythonProgramParses(): void
    {
        $process = new Process([
            'docker',
            'run',
            '--rm',
            '-i',
            '--entrypoint',
            'python3',
            self::getSemgrepImage(),
            '-I',
            '-c',
            'import sys; compile(sys.stdin.read(), "program", "exec")',
        ], input: $this->dumpVariable(self::PYTHON_PROGRAM_VARIABLE));

        $process->run();

        self::assertTrue($process->isSuccessful(), $process->getErrorOutput());
    }

    #[Group('container')]
    #[Group('make4')]
    public function testEveryDryRunRecipePassesShellcheck(): void
    {
        $targets       = Str::split(Str::trim($this->dumpVariable('_ALL_TARGETS', self::TOOLS_DIRECTORY)), ' ');
        $recipesPath   = $this->createFixtureRoot() . '/recipes';
        $filesystem    = new Filesystem();
        $runs          = [];
        $recipeScripts = [];

        foreach ($targets as $target) {
            if (Str::isNonDecimalIntString($target)) {
                $runs[$target] = [
                    'args' => [
                        '-n',
                        'MAKE=:',
                        $target,
                    ],
                    'directory' => self::TOOLS_DIRECTORY,
                ];
            }
        }

        if ($runs === []) {
            self::fail('The tools fixture lists no targets.');
        }

        $dryRuns = self::runMakeAllowingRefusals($runs);

        $successfulDryRuns = array_filter($dryRuns, static fn (Process $process): bool => $process->isSuccessful());

        foreach ($successfulDryRuns as $target => $dryRun) {
            $recipeScript    = $target . '.sh';
            $recipeScripts[] = $recipeScript;

            $filesystem->dumpFile(
                $recipesPath . '/' . $recipeScript,
                Str::replaceMatches($dryRun->getOutput(), '/^make(?:\[\d+\])?: .*$/m', static fn (): string => ''),
            );
        }

        $process = new Process([
            'docker',
            'run',
            '--rm',
            '-v',
            $recipesPath . ':/recipes:ro',
            '-w',
            '/recipes',
            '--entrypoint',
            'shellcheck',
            self::getImage(),
            '-s',
            'sh',
            '-S',
            'warning',
            '-e',
            Str::join(self::DRY_RUN_ARTIFACT_CODES, ','),
            ...$recipeScripts,
        ]);

        $process->run();

        self::assertGreaterThan(count($targets) / 2, count($recipeScripts), 'fewer than half the targets dry-run');
        self::assertTrue($process->isSuccessful(), $process->getOutput());
    }

    /**
     * @return list<string>
     */
    private static function getPhpProgramVariableNames(): array
    {
        $phpProgramVariableNames = [];

        foreach (self::provideEveryEmbeddedProgramParsesCases() as [$variableName]) {
            if (Str::endsWith($variableName, self::PHP_PROGRAM_SUFFIX)) {
                $phpProgramVariableNames[] = $variableName;
            }
        }

        return $phpProgramVariableNames;
    }

    private function runOverEveryPhpProgram(string $target): string
    {
        $fixtureRoot       = $this->createFixtureRoot();
        $programsDirectory = $fixtureRoot . '/programs';
        $filesystem        = new Filesystem();

        foreach (self::getPhpProgramVariableNames() as $variableName) {
            $filesystem->dumpFile(
                $programsDirectory . '/' . $variableName . '.php',
                self::PHP_OPENING_TAG . $this->dumpVariable($variableName) . "\n",
            );
        }

        return $this->runMake(
            [
                $target,
                '--',
                $programsDirectory,
            ],
            [
                'CONFIG'          => 'dist',
                'PHP_STAN_CONFIG' => self::PHPSTAN_CONFIG_PATH,
                'CACHE_DIR'       => $fixtureRoot . '/.cache',
            ],
            self::PROJECT_DIRECTORY,
        );
    }

    private function dumpVariable(string $variableName, ?string $directory = null): string
    {
        $process = self::createMakeProcess([
            '--eval',
            sprintf('%s: export _DUMP = $(%s)', self::DUMP_GOAL, $variableName),
            '--eval',
            self::DUMP_GOAL . ': ; @printf "%s" "$$_DUMP"',
            self::DUMP_GOAL,
        ], [], $directory);

        $process->mustRun();

        return $process->getOutput();
    }
}
