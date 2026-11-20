<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Tool;

use Brnshkr\Config\Json;
use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\ProjectDirectory;
use Brnshkr\Config\Str;
use Exception;
use JsonException;
use RuntimeException;
use SimpleXMLElement;
use Symfony\AI\Mate\Attribute\MateTool;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;

use function array_filter;
use function array_intersect_assoc;
use function array_key_exists;
use function array_keys;
use function array_values;
use function escapeshellarg;
use function hash_file;
use function is_array;
use function is_file;
use function is_readable;
use function is_string;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal
 */
final class TestTool
{
    private const string SNAPSHOT_PATHSPEC = ':(glob)**/__snapshots__/**';

    /**
     * @phpstan-var non-empty-array<'php'|'js', array{
     *     runner: non-empty-string,
     *     filterPrefix: string,
     *     noTestsFailingFlag: string,
     *     reportFlag: non-empty-string,
     *     reportExtension: non-empty-string,
     * }>
     */
    private const array SUITE_MAP = [
        'php' => [
            'runner'             => 'phpunit',
            'filterPrefix'       => '--filter ',
            'noTestsFailingFlag' => '',
            'reportFlag'         => '--log-junit %s',
            'reportExtension'    => '.xml',
        ],
        'js' => [
            'runner'             => 'vitest',
            'filterPrefix'       => '',
            'noTestsFailingFlag' => '--no-passWithNoTests',
            'reportFlag'         => '--reporter=default --reporter=json --outputFile.json=%s',
            'reportExtension'    => '.json',
        ],
    ];

    /**
     * @param string $suite - the test suite to run ("php" runs PHPUnit or Pest, "js" runs Vitest, both via make)
     * @param string $filter - runs a subset of tests; the runner's --filter value (e.g. a test class name) for "php", a file name filter for "js"; empty runs the full suite
     * @param bool $doUpdateSnapshots - when true, runs with snapshot updates instead of a plain run
     * @param string $arguments - passed on to the runner after the filter, e.g. a test file path
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws IOException
     * @throws JsonException
     * @throws LogicException
     * @throws ProcessRuntimeException
     * @throws RuntimeException
     */
    #[MateTool(
        name: 'brnshkr-test-run',
        description: 'Runs a test suite of this repository ("php" = PHPUnit or Pest, "js" = Vitest) and answers each failure with its test, file, line and message. Supports filtering, extra runner arguments and updating snapshots; reports which snapshot files the run changed, and which were already changed and left alone.',
    )]
    public function runTests(string $suite = 'php', string $filter = '', bool $doUpdateSnapshots = false, string $arguments = ''): string
    {
        $suiteSettings = self::SUITE_MAP[$suite] ?? null;

        if ($suiteSettings === null) {
            return Project::encodeUnknownValue('suite', $suite, array_keys(self::SUITE_MAP));
        }

        $filesystem      = new Filesystem();
        $reportDirectory = Project::getRootDirectory() . '/' . ProjectDirectory::getOwnCache();

        $filesystem->mkdir($reportDirectory);

        $reportPath              = $filesystem->tempnam($reportDirectory, 'test-run-', $suiteSettings['reportExtension']);
        $snapshotHashesBeforeRun = $this->getDirtySnapshotHashes();
        $result                  = $this->runSuite($suiteSettings, $filter, $doUpdateSnapshots, $arguments, $reportPath);
        $snapshotHashesAfterRun  = $this->getDirtySnapshotHashes();
        $report                  = $filesystem->readFile($reportPath);

        $filesystem->remove($reportPath);

        $failures = $suite === 'php'
            ? self::getPhpFailures($report)
            : self::getJsFailures($report);

        $extraFields = array_filter([
            'failures'                => $failures,
            'changedSnapshots'        => $this->getPathsWithDifferentHashes($snapshotHashesBeforeRun, $snapshotHashesAfterRun),
            'unchangedDirtySnapshots' => array_keys(array_intersect_assoc($snapshotHashesAfterRun, $snapshotHashesBeforeRun)),
        ], static fn (array $listedValues): bool => $listedValues !== []);

        if ($failures === [] && $result['exitCode'] !== 0) {
            return Project::encodeResult($result, $extraFields);
        }

        return Project::encode([
            'exitCode' => $result['exitCode'],
            ...$extraFields,
        ]);
    }

    /**
     * @param array{
     *     runner: non-empty-string,
     *     filterPrefix: string,
     *     noTestsFailingFlag: string,
     *     reportFlag: non-empty-string,
     *     reportExtension: non-empty-string,
     * } $suiteSettings
     *
     * @return array{
     *     exitCode: int,
     *     output: string,
     * }
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws ProcessRuntimeException
     * @throws RuntimeException
     */
    private function runSuite(array $suiteSettings, string $filter, bool $doUpdateSnapshots, string $arguments, string $reportPath): array
    {
        $runnerArguments = Str::trim(($filter === '' ? '' : $suiteSettings['filterPrefix'] . escapeshellarg($filter)) . ' ' . $arguments);
        $reportFlag      = sprintf($suiteSettings['reportFlag'], escapeshellarg(Path::makeRelative($reportPath, Project::getRootDirectory())));

        $result = Project::runTarget(
            $doUpdateSnapshots ? $suiteSettings['runner'] . '-update' : $suiteSettings['runner'],
            ['ARGS' => Str::trim(($runnerArguments === '' ? '' : $suiteSettings['noTestsFailingFlag'] . ' ') . $reportFlag . ' ' . $runnerArguments)],
        );

        return [
            ...$result,
            'output' => s($result['output'])->replaceMatches('/\A(?:[!.st⨯]+\n)+\n*/u', '')->toString(),
        ];
    }

    /**
     * @return array<non-empty-string, ?non-falsy-string>
     *
     * @throws LogicException
     * @throws ProcessRuntimeException
     * @throws RuntimeException
     */
    private function getDirtySnapshotHashes(): array
    {
        $status = Project::run([
            'git',
            'status',
            '--porcelain',
            '--untracked-files=all',
            '--',
            self::SNAPSHOT_PATHSPEC,
        ]);

        $snapshotHashes = [];

        foreach (Str::split($status['output'], "\n") as $statusLine) {
            $dirtySnapshotPath = Str::match(Str::trim($statusLine), '/^\S+\s+(?<path>\S.*)$/s')['path'] ?? '';

            if ($dirtySnapshotPath === '' || !Str::isNonDecimalIntString($dirtySnapshotPath)) {
                continue;
            }

            $absoluteSnapshotPath = Project::getRootDirectory() . '/' . $dirtySnapshotPath;

            $snapshotHashes[$dirtySnapshotPath] = is_file($absoluteSnapshotPath) && is_readable($absoluteSnapshotPath)
                ? (hash_file('xxh128', $absoluteSnapshotPath) ?: null)
                : null;
        }

        return $snapshotHashes;
    }

    /**
     * @return list<array{
     *     test: string,
     *     file: ?string,
     *     line: ?int,
     *     message: string,
     * }>
     *
     * @throws Exception
     * @throws RuntimeException
     */
    private static function getPhpFailures(string $report): array
    {
        if (Str::trim($report) === '') {
            return [];
        }

        $failedTestCases = new SimpleXMLElement($report)->xpath('//testcase[failure or error]');

        if (!is_array($failedTestCases)) {
            throw Project::createUnexpectedOutputException('phpunit');
        }

        $failures = [];

        foreach ($failedTestCases as $failedTestCase) {
            $testName        = (string) $failedTestCase['name'];
            $failureText     = Str::trim((string) ($failedTestCase->failure ?? $failedTestCase->error));
            $failureLocation = Str::match($failureText, '/\nat (?<file>\S+):(?<line>\d+)\z/');
            $failureMessage  = $failureLocation === [] ? $failureText : Str::beforeLast($failureText, "\nat ");

            $failures[] = [
                'test'    => $failedTestCase['class'] . '::' . $testName,
                'file'    => $failureLocation['file'] ?? null,
                'line'    => isset($failureLocation['line']) ? (int) $failureLocation['line'] : null,
                'message' => Str::trimPrefix($failureMessage, $testName),
            ];
        }

        return $failures;
    }

    /**
     * @return list<array{
     *     test: string,
     *     file: ?string,
     *     line: ?int,
     *     message: string,
     * }>
     *
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws RuntimeException
     */
    private static function getJsFailures(string $report): array
    {
        if (Str::trim($report) === '') {
            return [];
        }

        $decodedReport = Json::decode($report);

        if (!isset($decodedReport['testResults'])
            || !is_array($decodedReport['testResults'])) {
            throw Project::createUnexpectedOutputException('vitest');
        }

        $failures = [];

        foreach ($decodedReport['testResults'] as $testFile) {
            if (!is_array($testFile)
                || !isset($testFile['name'], $testFile['assertionResults'])
                || !is_string($testFile['name'])
                || !is_array($testFile['assertionResults'])) {
                throw Project::createUnexpectedOutputException('vitest');
            }

            foreach ($testFile['assertionResults'] as $assertionResult) {
                if (!is_array($assertionResult)
                    || !isset($assertionResult['status'], $assertionResult['fullName'], $assertionResult['failureMessages'])
                    || !is_string($assertionResult['fullName'])
                    || !is_array($assertionResult['failureMessages'])) {
                    throw Project::createUnexpectedOutputException('vitest');
                }

                if ($assertionResult['status'] !== 'failed') {
                    continue;
                }

                $failureText = Str::join(array_filter($assertionResult['failureMessages'], is_string(...)), "\n");
                $failureLine = Str::match($failureText, '/' . Str::quoteRegex($testFile['name']) . ':(?<line>\d+):\d+/')['line'] ?? null;

                $failures[] = [
                    'test'    => $assertionResult['fullName'],
                    'file'    => Path::makeRelative($testFile['name'], Project::getRootDirectory()),
                    'line'    => $failureLine === null ? null : (int) $failureLine,
                    'message' => Str::split($failureText, "\n    at ", 2)[0],
                ];
            }
        }

        return $failures;
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
