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

/**
 * Runs the test suites of this repository.
 *
 * @internal
 */
final class TestTool
{
    /**
     * @phpstan-var non-empty-array<'php'|'js', array{
     *     runner: non-empty-string,
     *     filterPrefix: string,
     *     snapshotPathspec: non-empty-string,
     * }>
     */
    private const array SUITE_MAP = [
        'php' => [
            'runner'           => 'pest',
            'filterPrefix'     => '--filter ',
            'snapshotPathspec' => ':(glob)tests/php/**/__snapshots__/*',
        ],
        'js' => [
            'runner'           => 'vitest',
            'filterPrefix'     => '',
            'snapshotPathspec' => ':(glob)tests/js/**/__snapshots__/*',
        ],
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
        $suiteSettings = self::SUITE_MAP[$suite] ?? null;

        if ($suiteSettings === null) {
            return Project::encodeUnknownValue('suite', $suite, array_keys(self::SUITE_MAP), [
                'changedSnapshots'        => [],
                'unchangedDirtySnapshots' => [],
            ]);
        }

        $snapshotHashesBeforeRun = $this->getDirtySnapshotHashes($suiteSettings['snapshotPathspec']);
        $result                  = $this->runSuite($suiteSettings, $filter, $doesUpdateSnapshots);
        $snapshotHashesAfterRun  = $this->getDirtySnapshotHashes($suiteSettings['snapshotPathspec']);

        return Project::encode([
            ...$result,
            'changedSnapshots'        => $this->getPathsWithDifferentHashes($snapshotHashesBeforeRun, $snapshotHashesAfterRun),
            'unchangedDirtySnapshots' => array_keys(array_intersect_assoc($snapshotHashesAfterRun, $snapshotHashesBeforeRun)),
        ]);
    }

    /**
     * @param array{
     *     runner: non-empty-string,
     *     filterPrefix: string,
     *     snapshotPathspec: non-empty-string,
     * } $suiteSettings
     *
     * @return array{
     *     exitCode: int,
     *     output: string,
     * }
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    private function runSuite(array $suiteSettings, string $filter, bool $doesUpdateSnapshots): array
    {
        return Project::runTarget(
            $doesUpdateSnapshots ? $suiteSettings['runner'] . '-update' : $suiteSettings['runner'],
            $filter === '' ? [] : ['ARGS' => $suiteSettings['filterPrefix'] . escapeshellarg($filter)],
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
     * @return list<non-empty-string>
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
