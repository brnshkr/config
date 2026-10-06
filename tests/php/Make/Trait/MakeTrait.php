<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make\Trait;

use Brnshkr\Config\Json;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

use function array_chunk;
use function array_first;
use function array_key_first;
use function array_map;
use function bin2hex;
use function getenv;
use function md5;
use function random_bytes;
use function realpath;
use function sprintf;
use function Symfony\Component\String\s;
use function sys_get_temp_dir;
use function uasort;

/**
 * @internal
 *
 * @phpstan-require-extends TestCase
 */
trait MakeTrait
{
    protected const array COLORED_ENV = [
        'NO_COLOR'    => '',
        'FORCE_COLOR' => '1',
    ];

    protected const string PROJECT_DIRECTORY  = __DIR__ . '/../../../..';
    protected const string CONSUMER_DIRECTORY = __DIR__ . '/../../Fixtures/Make/Consumer';
    protected const string TOOLS_DIRECTORY    = __DIR__ . '/../../Fixtures/Make/Tools';

    private const string MAKEFILE_PATH       = self::PROJECT_DIRECTORY . '/conf/Makefile';
    private const string FIXTURES_DIRECTORY  = __DIR__ . '/../../Fixtures/Make/Help';
    private const string FIXTURE_ROOT_PREFIX = 'brnshkr-make-fixture-';
    private const int CONCURRENT_RUNS        = 2;

    private const array UNLINKED_PROJECT_ENTRIES = [
        '.cache',
        '.git',
        '.local',
        'tests',
    ];

    private const array BASELINE_ENV = [
        'MAKEFLAGS'         => '',
        'NO_COLOR'          => '1',
        'WSL_DISTRO_NAME'   => '',
        'TERM_PROGRAM'      => '',
        'TERMINAL_EMULATOR' => '',
        'EDITOR'            => '',
        'LANG'              => '',
    ];

    /**
     * @var array<array-key, string>
     */
    private array $fixtureCopies = [];

    /**
     * @var list<string>
     */
    private array $fixtureRoots = [];

    #[After]
    public function removeTheFixtureRoots(): void
    {
        foreach ($this->fixtureRoots as $fixtureRoot) {
            if (!Str::startsWith($fixtureRoot, sys_get_temp_dir() . '/' . self::FIXTURE_ROOT_PREFIX)) {
                throw new RuntimeException(sprintf('`%s` is no fixture root, so it is not removed.', $fixtureRoot));
            }
        }

        new Filesystem()->remove($this->fixtureRoots);
    }

    private function getFixtureCopy(string $fixtureDirectory): string
    {
        return $this->fixtureCopies[$fixtureDirectory] ??= $this->createFixtureCopy($fixtureDirectory);
    }

    private function createFixtureCopy(string $fixtureDirectory): string
    {
        $fixtureCopy = $this->createFixtureRoot() . '/' . self::getProjectRelativePath($fixtureDirectory);

        new Filesystem()->mirror($fixtureDirectory, $fixtureCopy);
        new Process(['git', 'init', '--quiet'], $fixtureCopy)->mustRun();
        new Process(['git', 'add', '--all'], $fixtureCopy)->mustRun();

        return $fixtureCopy;
    }

    private function createFixtureRoot(): string
    {
        $fixtureRoot = sys_get_temp_dir() . '/' . self::FIXTURE_ROOT_PREFIX . bin2hex(random_bytes(6));
        $filesystem  = new Filesystem();

        $this->fixtureRoots[] = $fixtureRoot;

        $finder = new Finder()
            ->in(self::getRealPath(self::PROJECT_DIRECTORY))
            ->depth(0)
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->notName(self::UNLINKED_PROJECT_ENTRIES)
        ;

        foreach ($finder as $projectEntry) {
            $filesystem->symlink($projectEntry->getPathname(), $fixtureRoot . '/' . $projectEntry->getFilename());
        }

        return $fixtureRoot;
    }

    private static function getProjectRelativePath(string $path): string
    {
        return s(self::getRealPath($path))->after(self::getRealPath(self::PROJECT_DIRECTORY) . '/')->toString();
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
        $process = self::createMakeProcess($args, $env, $directory);

        $process->run();

        return $this->readMakeOutput($process, $doExpectFailure);
    }

    /**
     * @template TName of string
     *
     * @param non-empty-array<TName, array{
     *     args?: list<string>,
     *     env?: array<string, string>,
     *     directory?: string,
     * }> $runs
     *
     * @return non-empty-array<TName, string>
     */
    private function runMakeConcurrently(array $runs): array
    {
        return array_map(
            fn (Process $process): string => $this->readMakeOutput($process, false),
            self::runMakeAllowingRefusals($runs),
        );
    }

    /**
     * @template TName of string
     *
     * @param non-empty-array<TName, array{
     *     args?: list<string>,
     *     env?: array<string, string>,
     *     directory?: string,
     * }> $runs
     *
     * @return non-empty-array<TName, Process>
     */
    private static function runMakeAllowingRefusals(array $runs): array
    {
        $processes = array_map(
            static fn (array $run): Process => self::createMakeProcess(
                $run['args'] ?? [],
                $run['env'] ?? [],
                $run['directory'] ?? null,
            ),
            $runs,
        );

        foreach (array_chunk($processes, self::CONCURRENT_RUNS) as $batch) {
            foreach ($batch as $process) {
                $process->start();
            }

            foreach ($batch as $process) {
                $process->wait();
            }
        }

        return $processes;
    }

    /**
     * @param list<string> $args
     * @param array<string, string> $env
     */
    private static function createMakeProcess(array $args, array $env, ?string $directory): Process
    {
        return new Process([
            'make',
            '--no-print-directory',
            ...($directory === null ? ['-f', self::MAKEFILE_PATH] : []),
            '-C',
            $directory ?? self::FIXTURES_DIRECTORY,
            ...$args,
        ], env: [
            ...self::getBaselineEnvironment(),
            ...$env,
        ]);
    }

    private function readMakeOutput(Process $process, bool $doExpectFailure): string
    {
        if (!$doExpectFailure && !$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                "`%s` failed:\n%s\n%s",
                $process->getCommandLine(),
                $process->getOutput(),
                $process->getErrorOutput(),
            ));
        }

        return $this->normalizeOutput($process->getOutput() . $process->getErrorOutput());
    }

    private function normalizeOutput(string $output): string
    {
        return s($output)
            ->replaceMatches(sprintf('/%s/', Str::quoteRegex(self::PROJECT_DIRECTORY)), '.')
            ->toString()
        ;
    }

    /**
     * @return array<string, false|string>
     */
    private static function getBaselineEnvironment(): array
    {
        return [
            ...array_map(static fn (): false => false, getenv()),
            'HOME' => Str::fromEnvironment('HOME'),
            'PATH' => Str::fromEnvironment('PATH') ?: throw new RuntimeException('`PATH` is not set.'),
            ...self::BASELINE_ENV,
        ];
    }

    private static function getRealPath(string $path): string
    {
        return realpath($path) ?: throw new RuntimeException(sprintf('`%s` does not exist.', $path));
    }

    private static function assertContainsSymbol(string $symbol, string $output): void
    {
        /** @disregard P1013 \@phpstan-require-extends is not recognized by intelephense (See: https://github.com/bmewburn/vscode-intelephense/issues/3256) */
        self::assertMatchesRegularExpression(
            sprintf('/(?<![\w.-])%s(?![\w.-])/', Str::quoteRegex($symbol)),
            $output,
            sprintf('expected %s in output', $symbol),
        );
    }

    private static function assertDoesNotContainSymbol(string $symbol, string $output): void
    {
        /** @disregard P1013 \@phpstan-require-extends is not recognized by intelephense (See: https://github.com/bmewburn/vscode-intelephense/issues/3256) */
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
            sprintf('make --no-print-directory -C %s %s', $directory, Str::join($args, ' ')),
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
