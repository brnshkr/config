<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Json;
use Brnshkr\Config\ProjectDirectory;
use Brnshkr\Config\Spelling;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function array_column;
use function array_unique;
use function array_values;
use function bin2hex;
use function dirname;
use function random_bytes;
use function sprintf;
use function sys_get_temp_dir;

/**
 * @internal
 */
#[CoversClass(Spelling::class)]
#[UsesClass(ProjectDirectory::class)]
#[UsesClass(Json::class)]
#[UsesClass(Str::class)]
final class SpellingTest extends TestCase
{
    private const string CORPUS_CONFIG = 'tests/php/Fixtures/Spelling/spelling.json';

    private const string EXTRA_SUFFIX_CONFIG = 'tests/php/Fixtures/Spelling/extra-suffix.json';

    private const array CORPUS_PATHS = [
        'tests/php/Fixtures/Spelling/prose.md',
        'tests/php/Fixtures/Spelling/identifiers.php',
    ];

    private const string TEMPORARY_ROOT_PREFIX = 'brnshkr-spelling-';

    /**
     * @var list<non-empty-string>
     */
    private array $temporaryRoots = [];

    #[After]
    public function removeTheTemporaryRoots(): void
    {
        foreach ($this->temporaryRoots as $temporaryRoot) {
            if (!Str::startsWith($temporaryRoot, sys_get_temp_dir() . '/' . self::TEMPORARY_ROOT_PREFIX)) {
                throw new RuntimeException(sprintf('`%s` is no temporary root, so it is not removed.', $temporaryRoot));
            }
        }

        new Filesystem()->remove($this->temporaryRoots);
    }

    public function testReportsBritishSpellingsWithTheirCorrection(): void
    {
        $findings = $this->scanCorpus();

        self::assertContains('behaviour', array_column($findings, 'word'));
        self::assertContains('behavior', array_column($findings, 'suggestion'));
        self::assertContains('recognizes', array_column($findings, 'suggestion'));
    }

    public function testAllowsAWordOnlyOnTheLineTheLiteralNames(): void
    {
        $lines = [];

        foreach ($this->scanCorpus() as $finding) {
            if ($finding['path'] === 'tests/php/Fixtures/Spelling/prose.md' && $finding['word'] === 'analyse') {
                $lines[] = $finding['line'];
            }
        }

        self::assertNotContains(9, $lines, '"analyse:9" allows the word on line nine');
        self::assertContains(11, $lines, '"analyse:9" must not reach line eleven');
    }

    public function testSkipsAWordCoveredByAnAllowedLiteral(): void
    {
        foreach ($this->scanCorpus() as $finding) {
            self::assertNotSame(7, $finding['line'], '"phpstan-analyse" must cover the spelling inside it');
        }
    }

    public function testRejectsAnAllowedWordAlreadyAllowedEverywhere(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('is already covered by "analyse"');

        Spelling::scan(
            $this->getRepositoryRoot(),
            'tests/php/Fixtures/Spelling/covered-everywhere.json',
            self::CORPUS_PATHS,
        );
    }

    public function testRejectsALineSuppressionTheWholeFileAlreadyAllows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Allowed word "analyse:9"');

        Spelling::scan(
            $this->getRepositoryRoot(),
            'tests/php/Fixtures/Spelling/covered-by-file.json',
            self::CORPUS_PATHS,
        );
    }

    public function testRejectsALineSuppressionABroaderLineListAlreadyAllows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('is already covered by "analyse:9,11"');

        Spelling::scan(
            $this->getRepositoryRoot(),
            'tests/php/Fixtures/Spelling/covered-by-lines.json',
            self::CORPUS_PATHS,
        );
    }

    public function testMergesADeclaredStemSuffixIntoTheShippedOnes(): void
    {
        $words = array_column($this->scanCorpus(self::EXTRA_SUFFIX_CONFIG), 'word');

        self::assertContains('analysoid', $words, '"oid" is declared and has to be scanned for');
        self::assertContains('normalises', $words, 'A declared suffix must not replace the shipped ones');
    }

    public function testScansOnlyTheTrackedFilesTheSettingsName(): void
    {
        $repositoryRoot = $this->createTemporaryRoot();
        $filesystem     = new Filesystem();

        foreach (['notes.md', 'Makefile', 'notes.txt', 'tests/fixtures/notes.md', 'gone.md', 'untracked.md'] as $filePath) {
            $filesystem->dumpFile($repositoryRoot . '/' . $filePath, "colour\n");
        }

        new Process(['git', 'init', '--quiet'], $repositoryRoot)->mustRun();
        new Process(['git', 'add', '--', 'notes.md', 'Makefile', 'notes.txt', 'tests/fixtures/notes.md', 'gone.md'], $repositoryRoot)->mustRun();
        $filesystem->remove($repositoryRoot . '/gone.md');

        $paths = array_column(Spelling::scan($repositoryRoot), 'path')
            |> array_unique(...)
            |> array_values(...);

        self::assertSame(['Makefile', 'notes.md'], $paths);
    }

    public function testFailsNamingTheDirectoryGitCannotList(): void
    {
        $directory = $this->createTemporaryRoot();

        new Filesystem()->mkdir($directory);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains(sprintf('"git ls-files -z" failed in "%s" with exit code 128.', $directory));

        Spelling::scan($directory);
    }

    #[TestWith(['tests/php/Fixtures/Spelling/stem-suffix-not-string.json', 'Setting "stemSuffixes" takes strings only.'])]
    #[TestWith(['tests/php/Fixtures/Spelling/stem-suffix-not-list.json', 'Setting "stemSuffixes" must name at least one suffix.'])]
    public function testRejectsAMalformedStemSuffixSetting(string $configPath, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains($message);

        $this->scanCorpus($configPath);
    }

    #[TestWith([self::CORPUS_CONFIG])]
    #[TestWith([self::EXTRA_SUFFIX_CONFIG])]
    public function testTheJavaScriptScannerReportsTheSameFindings(string $configPath): void
    {
        $program = sprintf(
            'import { scan } from "./src/js/spelling";'
            . 'process.stdout.write(JSON.stringify(scan({ configPath: %s, paths: %s })));',
            Json::encode($configPath),
            Json::encode(self::CORPUS_PATHS),
        );

        $process = new Process(['bun', '--bun', '-e', $program], $this->getRepositoryRoot());

        $process->run();
        self::assertTrue($process->isSuccessful(), $process->getErrorOutput());

        self::assertSame(
            Json::encode($this->scanCorpus($configPath)),
            Json::encode(Json::decode($process->getOutput())),
        );
    }

    /**
     * @return list<array{
     *     path: string,
     *     line: int,
     *     word: string,
     *     suggestion: string,
     * }>
     */
    private function scanCorpus(string $configPath = self::CORPUS_CONFIG): array
    {
        return Spelling::scan($this->getRepositoryRoot(), $configPath, self::CORPUS_PATHS);
    }

    /**
     * @return non-empty-string
     */
    private function createTemporaryRoot(): string
    {
        $temporaryRoot = sys_get_temp_dir() . '/' . self::TEMPORARY_ROOT_PREFIX . bin2hex(random_bytes(6));

        $this->temporaryRoots[] = $temporaryRoot;

        return $temporaryRoot;
    }

    /**
     * @return non-empty-string
     */
    private function getRepositoryRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
