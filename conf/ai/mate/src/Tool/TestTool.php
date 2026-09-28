<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Tool;

use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Str;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;

use function array_filter;
use function array_intersect_assoc;
use function array_key_exists;
use function array_keys;
use function array_values;
use function escapeshellarg;
use function explode;
use function hash_file;
use function is_file;
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
        description: 'Runs a test suite of this repository ("php" = Pest, "js" = Vitest). Supports filtering and updating snapshots; reports which snapshot files the run changed, and which were already changed and left alone.',
    )]
    public function runTests(string $suite = 'php', string $filter = '', bool $doesUpdateSnapshots = false): string
    {
        $snapshotPathspec = self::SNAPSHOT_PATHSPEC_MAP[$suite] ?? null;

        if ($snapshotPathspec === null) {
            return Project::encode([
                'exitCode'                => 1,
                'output'                  => sprintf('Unknown suite "%s". Valid suites: "php", "js".', $suite),
                'changedSnapshots'        => [],
                'unchangedDirtySnapshots' => [],
            ]);
        }

        $snapshotHashesBeforeRun = $this->getDirtySnapshotHashes($snapshotPathspec);
        $result                  = $this->runSuite($suite, $filter, $doesUpdateSnapshots);
        $snapshotHashesAfterRun  = $this->getDirtySnapshotHashes($snapshotPathspec);

        return Project::encode([
            ...$result,
            'changedSnapshots'        => $this->getPathsWithDifferentHashes($snapshotHashesBeforeRun, $snapshotHashesAfterRun),
            'unchangedDirtySnapshots' => array_keys(array_intersect_assoc($snapshotHashesAfterRun, $snapshotHashesBeforeRun)),
        ]);
    }

    /**
     * @return array{
     *     exitCode: int,
     *     output: string,
     * }
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    private function runSuite(string $suite, string $filter, bool $doesUpdateSnapshots): array
    {
        $runner         = $suite === 'js' ? 'vitest' : 'pest';
        $filterArgument = $suite === 'js' ? escapeshellarg($filter) : '--filter ' . escapeshellarg($filter);

        return Project::runTarget(
            $doesUpdateSnapshots ? $runner . '-update' : $runner,
            $filter === '' ? [] : ['ARGS' => $filterArgument],
        );
    }

    /**
     * @param non-empty-string $snapshotPathspec
     *
     * @return array<non-empty-string, ?non-falsy-string>
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    private function getDirtySnapshotHashes(string $snapshotPathspec): array
    {
        $status         = Project::run(['git', 'status', '--porcelain', '--untracked-files=all', '--', $snapshotPathspec]);
        $snapshotHashes = [];

        foreach (explode("\n", $status['output']) as $statusLine) {
            $dirtySnapshotPath = (preg_split('/\s+/', Str::trim($statusLine), 2) ?: [])[1] ?? '';

            if ($dirtySnapshotPath === '' || !Str::isNonDecimalIntString($dirtySnapshotPath)) {
                continue;
            }

            $absoluteSnapshotPath = Project::getRootDirectory() . '/' . $dirtySnapshotPath;

            $snapshotHashes[$dirtySnapshotPath] = is_file($absoluteSnapshotPath)
                ? (hash_file('xxh128', $absoluteSnapshotPath) ?: null)
                : null;
        }

        return $snapshotHashes;
    }

    /**
     * @param array<non-empty-string, ?non-falsy-string> $snapshotHashesBeforeRun
     * @param array<non-empty-string, ?non-falsy-string> $snapshotHashesAfterRun
     *
     * @return list<string>
     */
    private function getPathsWithDifferentHashes(array $snapshotHashesBeforeRun, array $snapshotHashesAfterRun): array
    {
        return array_values(array_filter(
            array_keys([...$snapshotHashesBeforeRun, ...$snapshotHashesAfterRun]),
            static fn (string $path): bool => array_key_exists($path, $snapshotHashesBeforeRun) !== array_key_exists($path, $snapshotHashesAfterRun)
                || ($snapshotHashesBeforeRun[$path] ?? null) !== ($snapshotHashesAfterRun[$path] ?? null),
        ));
    }
}
