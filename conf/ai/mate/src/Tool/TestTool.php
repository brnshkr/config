<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Tool;

use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Str;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;

use function array_filter;
use function array_map;
use function array_values;
use function escapeshellarg;
use function explode;
use function preg_split;
use function sprintf;

/**
 * Runs the test suites of this repository.
 *
 * @internal
 */
final class TestTool
{
    private const array SNAPSHOT_PATHSPEC_MAP = [
        'php' => ':(glob)tests/php/**/__snapshots__/*',
        'js'  => ':(glob)tests/js/**/__snapshots__/*',
    ];

    /**
     * @param string $suite the test suite to run ("php" runs Pest, "js" runs Vitest, both via make)
     * @param string $filter runs a subset of tests; a Pest --filter value (e.g. a test class name) for "php", a file name filter for "js"; empty runs the full suite
     * @param bool $doesUpdateSnapshots when true, runs with snapshot updates instead of a plain run
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    #[McpTool(
        name: 'project-tests-run',
        description: 'Runs a test suite of this repository ("php" = Pest, "js" = Vitest). Supports filtering and updating snapshots; reports which snapshot files changed afterwards.',
    )]
    public function runTests(string $suite = 'php', string $filter = '', bool $doesUpdateSnapshots = false): string
    {
        $snapshotPathspec = self::SNAPSHOT_PATHSPEC_MAP[$suite] ?? null;

        if ($snapshotPathspec === null) {
            return Project::encode([
                'exitCode'         => 1,
                'output'           => sprintf('Unknown suite "%s". Valid suites: "php", "js".', $suite),
                'changedSnapshots' => [],
            ]);
        }

        $result = Project::run($this->getCommand($suite, $filter, $doesUpdateSnapshots));

        return Project::encode([
            ...$result,
            'changedSnapshots' => $this->getChangedSnapshots($snapshotPathspec),
        ]);
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    private function getCommand(string $suite, string $filter, bool $doesUpdateSnapshots): array
    {
        if ($suite === 'js') {
            $command = ['make', 'NO_ANSI=1', $doesUpdateSnapshots ? 'vitest-update' : 'vitest'];

            if ($filter !== '') {
                return [...$command, 'ARGS=' . escapeshellarg($filter)];
            }

            return $command;
        }

        $command = ['make', 'NO_ANSI=1', $doesUpdateSnapshots ? 'pest-update' : 'pest'];

        if ($filter !== '') {
            return [...$command, 'ARGS=--filter ' . escapeshellarg($filter)];
        }

        return $command;
    }

    /**
     * @param non-empty-string $snapshotPathspec
     *
     * @return list<string>
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    private function getChangedSnapshots(string $snapshotPathspec): array
    {
        $status = Project::run(['git', 'status', '--porcelain', '--untracked-files=all', '--', $snapshotPathspec]);

        return array_values(array_filter(
            array_map(
                static fn (string $line): string => (preg_split('/\s+/', Str::trim($line), 2) ?: [])[1] ?? '',
                explode("\n", $status['output']),
            ),
            static fn (string $path): bool => $path !== '',
        ));
    }
}
