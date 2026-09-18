<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Json;
use Brnshkr\Config\Spelling;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

use function array_column;
use function dirname;
use function sprintf;

/**
 * @internal
 */
#[CoversClass(Spelling::class)]
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
    private function getRepositoryRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
