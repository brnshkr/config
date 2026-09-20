<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use Brnshkr\Config\Tests\SpellingTest;
use Composer\InstalledVersions;
use JsonException;
use OutOfBoundsException;
use RuntimeException;

use function array_any;
use function array_is_list;
use function array_map;
use function array_values;
use function basename;
use function explode;
use function fclose;
use function feof;
use function file_get_contents;
use function fread;
use function function_exists;
use function implode;
use function in_array;
use function is_array;
use function is_file;
use function is_readable;
use function is_string;
use function max;
use function mb_substr;
use function microtime;
use function proc_close;
use function proc_open;
use function proc_terminate;
use function sprintf;
use function stream_select;
use function stream_set_blocking;
use function usort;

use const STDERR;

/**
 * Reports British spellings in a repository's tracked prose, docblocks and identifiers.
 *
 * The word list and the scanned file surface are shipped by `brnshkr/config`, so every repository checks the same words.
 * A repository's `conf/spelling.json` may add to any of it and may allow words the list still rejects,
 * but it may not remove a rule. The JavaScript implementation reports the same findings
 * and is held to it by a parity test.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @see https://github.com/brnshkr/config/blob/master/src/js/spelling/index.ts
 * @see SpellingTest
 */
final class Spelling
{
    public const string DEFAULT_CONFIG_PATH = 'conf/spelling.json';

    private const string DEFAULTS_PATH = 'conf/spelling/defaults.json';

    private const string EVERY_PATH = '*';

    private const int COMMAND_TIMEOUT_SECONDS = 60;

    private const int READ_CHUNK_BYTES = 8_192;

    /**
     * Scan a repository and return every finding, sorted by path, line and word.
     *
     * @param non-empty-string $rootDirectory repository root the scan runs against
     * @param string $configPath repository config path, relative to the root
     * @param ?list<string> $paths files to scan, derived from the tracked files when null
     *
     * @return list<array{
     *     path: string,
     *     line: int,
     *     word: string,
     *     suggestion: string,
     * }>
     *
     * @throws JsonException
     * @throws RuntimeException
     *
     * @example
     * ```php
     * Spelling::scan(__DIR__);
     * ```
     */
    public static function scan(
        string $rootDirectory,
        string $configPath = self::DEFAULT_CONFIG_PATH,
        ?array $paths = null,
    ): array {
        $settings  = self::readSettings($rootDirectory . '/' . $configPath);
        $patterns  = self::buildPatterns($settings);
        $allowlist = self::readAllowlist($settings);
        $filePaths = $paths ?? self::collectFilePaths($rootDirectory, $settings);
        $findings  = [];

        foreach ($filePaths as $filePath) {
            $fileContents = self::readFile($rootDirectory . '/' . $filePath);

            if ($fileContents === null) {
                continue;
            }

            $findings = [...$findings, ...self::findInFile($fileContents, $filePath, $patterns, $allowlist)];
        }

        usort($findings, self::compareFindings(...));

        return $findings;
    }

    /**
     * @param array{
     *     path: string,
     *     line: int,
     *     word: string,
     *     suggestion: string,
     * } $firstFinding
     * @param array{
     *     path: string,
     *     line: int,
     *     word: string,
     *     suggestion: string,
     * } $secondFinding
     */
    private static function compareFindings(array $firstFinding, array $secondFinding): int
    {
        return self::compareCaseInsensitively($firstFinding['path'], $secondFinding['path'])
            ?: $firstFinding['line'] <=> $secondFinding['line']
            ?: self::compareCaseInsensitively($firstFinding['word'], $secondFinding['word']);
    }

    /**
     * @param list<array{
     *     pattern: string,
     *     britishPrefix: string,
     *     americanPrefix: string,
     * }> $patterns
     * @param array<array-key, list<array{
     *     text: string,
     *     lineNumbers: ?list<int>,
     * }>> $allowlist
     *
     * @return list<array{
     *     path: string,
     *     line: int,
     *     word: string,
     *     suggestion: string,
     * }>
     */
    private static function findInFile(
        string $fileContents,
        string $filePath,
        array $patterns,
        array $allowlist,
    ): array {
        $findings = [];

        $allowedLiterals = [
            ...$allowlist[self::EVERY_PATH] ?? [],
            ...$allowlist[$filePath] ?? [],
        ];

        foreach (explode("\n", $fileContents) as $index => $line) {
            $lineNumber = $index + 1;
            $maskedLine = self::maskAllowedLiterals($line, $allowedLiterals, $lineNumber);

            foreach ($patterns as $pattern) {
                foreach (Str::matchAll($maskedLine, $pattern['pattern']) as $matchedGroups) {
                    $word = $matchedGroups[0] ?? '';

                    $findings[] = [
                        'path'       => $filePath,
                        'line'       => $lineNumber,
                        'word'       => $word,
                        'suggestion' => self::toAmericanSpelling($word, $pattern),
                    ];
                }
            }
        }

        return $findings;
    }

    /**
     * @param list<array{
     *     text: string,
     *     lineNumbers: ?list<int>,
     * }> $allowedLiterals
     */
    private static function maskAllowedLiterals(string $line, array $allowedLiterals, int $lineNumber): string
    {
        foreach ($allowedLiterals as $allowedLiteral) {
            if ($allowedLiteral['lineNumbers'] !== null
                && !in_array($lineNumber, $allowedLiteral['lineNumbers'], true)) {
                continue;
            }

            $line = Str::replaceMatches(
                $line,
                '/' . Str::quoteRegex($allowedLiteral['text']) . '/i',
                static fn (array $matchedGroups): string => Str::repeat(
                    '.',
                    max(0, Str::length($matchedGroups[0] ?? '')),
                ),
            );
        }

        return $line;
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array<array-key, list<array{
     *     text: string,
     *     lineNumbers: ?list<int>,
     * }>>
     *
     * @throws RuntimeException
     */
    private static function readAllowlist(array $settings): array
    {
        $declaredAllowlist = is_array($settings['allowlist'] ?? null) ? $settings['allowlist'] : [];
        $allowlist         = [];

        foreach ($declaredAllowlist as $allowedPath => $declaredLiterals) {
            $allowlist[$allowedPath] = self::parseAllowedLiterals(
                array_values(is_array($declaredLiterals) ? $declaredLiterals : []),
            );
        }

        self::rejectCoveredLiterals($allowlist);

        return $allowlist;
    }

    /**
     * @param list<mixed> $declaredLiterals
     *
     * @return list<array{
     *     text: string,
     *     lineNumbers: ?list<int>,
     * }>
     */
    private static function parseAllowedLiterals(array $declaredLiterals): array
    {
        $allowedLiterals = [];

        foreach ($declaredLiterals as $declaredLiteral) {
            $literalText   = is_string($declaredLiteral) ? $declaredLiteral : '';
            $matchedGroups = Str::match($literalText, '/^(?<text>.*):(?<lineNumbers>\d+(?:,\d+)*)$/');

            $allowedLiterals[] = [
                'text'        => $matchedGroups['text'] ?? $literalText,
                'lineNumbers' => isset($matchedGroups['lineNumbers'])
                    ? array_map(intval(...), explode(',', $matchedGroups['lineNumbers']))
                    : null,
            ];
        }

        return $allowedLiterals;
    }

    /**
     * @param array<array-key, list<array{
     *     text: string,
     *     lineNumbers: ?list<int>,
     * }>> $allowlist
     *
     * @throws RuntimeException
     */
    private static function rejectCoveredLiterals(array $allowlist): void
    {
        $literalsAllowedEverywhere = $allowlist[self::EVERY_PATH] ?? [];

        foreach ($allowlist as $allowedPath => $allowedLiterals) {
            foreach ($allowedLiterals as $index => $allowedLiteral) {
                $broaderLiterals   = $allowedPath === self::EVERY_PATH ? [] : $literalsAllowedEverywhere;
                $precedingLiterals = self::getPrecedingLiterals($allowedLiterals, $index);

                foreach ([...$broaderLiterals, ...$precedingLiterals] as $coveringCandidate) {
                    if (self::isCoveredBy($coveringCandidate, $allowedLiteral)) {
                        throw new RuntimeException(sprintf(
                            'Allowed word "%s" under "%s" is already covered by "%s".',
                            self::formatAllowedLiteral($allowedLiteral),
                            $allowedPath,
                            self::formatAllowedLiteral($coveringCandidate),
                        ));
                    }
                }
            }
        }
    }

    /**
     * @param list<array{
     *     text: string,
     *     lineNumbers: ?list<int>,
     * }> $allowedLiterals
     *
     * @return list<array{
     *     text: string,
     *     lineNumbers: ?list<int>,
     * }>
     */
    private static function getPrecedingLiterals(array $allowedLiterals, int $index): array
    {
        $precedingLiterals = [];

        foreach ($allowedLiterals as $position => $allowedLiteral) {
            if ($position < $index) {
                $precedingLiterals[] = $allowedLiteral;
            }
        }

        return $precedingLiterals;
    }

    /**
     * @param array{
     *     text: string,
     *     lineNumbers: ?list<int>,
     * } $coveringCandidate
     * @param array{
     *     text: string,
     *     lineNumbers: ?list<int>,
     * } $allowedLiteral
     */
    private static function isCoveredBy(array $coveringCandidate, array $allowedLiteral): bool
    {
        if (Str::toLowerCase($coveringCandidate['text']) !== Str::toLowerCase($allowedLiteral['text'])) {
            return false;
        }

        if ($coveringCandidate['lineNumbers'] === null) {
            return true;
        }

        if ($allowedLiteral['lineNumbers'] === null) {
            return false;
        }

        foreach ($allowedLiteral['lineNumbers'] as $lineNumber) {
            if (!in_array($lineNumber, $coveringCandidate['lineNumbers'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array{
     *     text: string,
     *     lineNumbers: ?list<int>,
     * } $allowedLiteral
     */
    private static function formatAllowedLiteral(array $allowedLiteral): string
    {
        return $allowedLiteral['lineNumbers'] === null
            ? $allowedLiteral['text']
            : $allowedLiteral['text'] . ':' . implode(',', $allowedLiteral['lineNumbers']);
    }

    private static function compareCaseInsensitively(string $firstValue, string $secondValue): int
    {
        return Str::toLowerCase($firstValue) <=> Str::toLowerCase($secondValue) ?: $firstValue <=> $secondValue;
    }

    /**
     * @param array{
     *     pattern: string,
     *     britishPrefix: string,
     *     americanPrefix: string,
     * } $pattern
     */
    private static function toAmericanSpelling(string $word, array $pattern): string
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
        return $pattern['americanPrefix'] . mb_substr($word, Str::length($pattern['britishPrefix']));
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return list<array{
     *     pattern: string,
     *     britishPrefix: string,
     *     americanPrefix: string,
     * }>
     *
     * @throws RuntimeException
     */
    private static function buildPatterns(array $settings): array
    {
        $britishSpellings = is_array($settings['britishSpellings'] ?? null) ? $settings['britishSpellings'] : [];
        $britishStems     = is_array($settings['britishStems'] ?? null) ? $settings['britishStems'] : [];
        $stemSuffixGroup  = self::buildStemSuffixGroup($settings);

        $patterns = [];

        foreach ($britishSpellings as $britishSpelling => $americanSpelling) {
            $patterns[] = [
                'pattern'        => '/\b' . Str::quoteRegex((string) $britishSpelling) . '\w*/i',
                'britishPrefix'  => (string) $britishSpelling,
                'americanPrefix' => is_string($americanSpelling) ? $americanSpelling : '',
            ];
        }

        foreach ($britishStems as $britishStem) {
            $stemText = is_string($britishStem) ? $britishStem : '';

            $patterns[] = [
                'pattern'        => '/\b' . Str::quoteRegex($stemText) . $stemSuffixGroup . '\b/i',
                'britishPrefix'  => $stemText,
                'americanPrefix' => Str::trimSuffix($stemText, 's') . 'z',
            ];
        }

        return $patterns;
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @throws RuntimeException
     */
    private static function buildStemSuffixGroup(array $settings): string
    {
        $stemSuffixes = [];

        foreach (is_array($settings['stemSuffixes'] ?? null) ? $settings['stemSuffixes'] : [] as $stemSuffix) {
            if (!is_string($stemSuffix)) {
                throw new RuntimeException('Setting "stemSuffixes" takes strings only.');
            }

            $stemSuffixes[] = $stemSuffix;
        }

        if ($stemSuffixes === []) {
            throw new RuntimeException('Setting "stemSuffixes" must name at least one suffix.');
        }

        return '(?:' . implode('|', $stemSuffixes) . ')';
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return list<string>
     *
     * @throws RuntimeException
     */
    private static function collectFilePaths(string $rootDirectory, array $settings): array
    {
        $fileExtensions = array_values(is_array($settings['fileExtensions'] ?? null) ? $settings['fileExtensions'] : []);
        $fileNames      = array_values(is_array($settings['fileNames'] ?? null) ? $settings['fileNames'] : []);
        $ignorePatterns = array_values(is_array($settings['ignorePatterns'] ?? null) ? $settings['ignorePatterns'] : []);
        $filePaths      = [];

        foreach (explode("\0", self::runCommand(['git', 'ls-files', '-z'], $rootDirectory)) as $filePath) {
            if ($filePath === '') {
                continue;
            }

            if (!self::isScannedPath($filePath, $fileExtensions, $fileNames, $ignorePatterns)) {
                continue;
            }

            if (is_file($rootDirectory . '/' . $filePath)) {
                $filePaths[] = $filePath;
            }
        }

        return $filePaths;
    }

    /**
     * @param list<mixed> $fileExtensions
     * @param list<mixed> $fileNames
     * @param list<mixed> $ignorePatterns
     */
    private static function isScannedPath(
        string $filePath,
        array $fileExtensions,
        array $fileNames,
        array $ignorePatterns,
    ): bool {
        foreach ($ignorePatterns as $ignorePattern) {
            if (is_string($ignorePattern) && Str::match($filePath, '~' . $ignorePattern . '~') !== []) {
                return false;
            }
        }

        if (array_any($fileNames, static fn (mixed $fileName): bool => is_string($fileName) && basename($filePath) === $fileName)) {
            return true;
        }

        return array_any($fileExtensions, static fn (mixed $fileExtension): bool => is_string($fileExtension) && Str::endsWith($filePath, $fileExtension));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     * @throws RuntimeException
     */
    private static function readSettings(string $settingsPath): array
    {
        $settings     = self::readShippedDefaults();
        $fileContents = self::readFile($settingsPath);

        if ($fileContents === null) {
            return $settings;
        }

        foreach (Json::decode($fileContents) as $settingName => $declaredSetting) {
            $settings[$settingName] = self::mergeSetting($settings[$settingName] ?? null, $declaredSetting);
        }

        return $settings;
    }

    private static function mergeSetting(mixed $shippedSetting, mixed $declaredSetting): mixed
    {
        if (!is_array($shippedSetting) || !is_array($declaredSetting)) {
            return $declaredSetting;
        }

        if (!array_is_list($shippedSetting) || !array_is_list($declaredSetting)) {
            return [...$shippedSetting, ...$declaredSetting];
        }

        $mergedSetting = $shippedSetting;

        foreach ($declaredSetting as $declaredEntry) {
            if (!in_array($declaredEntry, $mergedSetting, true)) {
                $mergedSetting[] = $declaredEntry;
            }
        }

        return $mergedSetting;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     * @throws RuntimeException
     */
    private static function readShippedDefaults(): array
    {
        $fileContents = self::readFile(self::getPackageDirectory() . '/' . self::DEFAULTS_PATH);

        if ($fileContents === null) {
            throw new RuntimeException(
                sprintf('Shipped file "%s" is missing from "brnshkr/config".', self::DEFAULTS_PATH),
            );
        }

        return Json::decode($fileContents);
    }

    private static function readFile(string $filePath): ?string
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return null;
        }

        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/filesystem here to keep package as lightweight as possible)
        $fileContents = file_get_contents($filePath);

        return $fileContents === false ? null : $fileContents;
    }

    /**
     * @param list<string> $command
     *
     * @throws RuntimeException
     */
    private static function runCommand(array $command, string $workingDirectory): string
    {
        if (!function_exists('proc_open')) {
            throw new RuntimeException(
                sprintf('Running "%s" needs proc_open(), which this PHP installation disabled.', implode(' ', $command)),
            );
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => STDERR,
        ];

        $process = proc_open($command, $descriptors, $processPipes, $workingDirectory);

        if ($process === false) {
            throw new RuntimeException(sprintf('Unable to run "%s".', implode(' ', $command)));
        }

        if (!isset($processPipes[0], $processPipes[1])) {
            proc_close($process);

            throw new RuntimeException(sprintf('"%s" opened no pipes.', implode(' ', $command)));
        }

        fclose($processPipes[0]);

        $output   = self::readUntilEndOfFile($process, $processPipes[1], $command);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException(sprintf(
                '"%s" failed in "%s" with exit code %d.',
                implode(' ', $command),
                $workingDirectory,
                $exitCode,
            ));
        }

        return $output;
    }

    /**
     * @param resource $process
     * @param resource $pipe
     * @param list<string> $command
     *
     * @throws RuntimeException
     */
    private static function readUntilEndOfFile(mixed $process, mixed $pipe, array $command): string
    {
        stream_set_blocking($pipe, false);

        $deadline = microtime(true) + self::COMMAND_TIMEOUT_SECONDS;
        $output   = '';

        while (!feof($pipe)) {
            $remainingSeconds = $deadline - microtime(true);

            if ($remainingSeconds <= 0) {
                proc_terminate($process);
                fclose($pipe);
                proc_close($process);

                throw new RuntimeException(sprintf(
                    '"%s" did not finish within %d seconds.',
                    implode(' ', $command),
                    self::COMMAND_TIMEOUT_SECONDS,
                ));
            }

            $readable = [$pipe];
            $writable = [];
            $errored  = [];

            stream_select($readable, $writable, $errored, (int) $remainingSeconds, 0);

            $chunk = fread($pipe, self::READ_CHUNK_BYTES);

            if ($chunk === false) {
                break;
            }

            $output .= $chunk;
        }

        fclose($pipe);

        return $output;
    }

    /**
     * @throws RuntimeException
     */
    private static function getPackageDirectory(): string
    {
        $localPackagePath = __DIR__ . '/../..';

        if (is_file($localPackagePath . '/' . self::DEFAULTS_PATH)) {
            return $localPackagePath;
        }

        try {
            $installPath = InstalledVersions::getInstallPath('brnshkr/config');
        } catch (OutOfBoundsException $outOfBoundsException) {
            throw new RuntimeException('Package "brnshkr/config" is not installed.', $outOfBoundsException->getCode(), $outOfBoundsException);
        }

        if ($installPath === null) {
            throw new RuntimeException('Package "brnshkr/config" has no install path.');
        }

        return $installPath;
    }
}
