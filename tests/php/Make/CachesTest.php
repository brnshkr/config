<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

use function array_map;

/**
 * @internal
 */
#[CoversNothing]
final class CachesTest extends TestCase
{
    use MakeTrait;

    private const string CACHES_DIRECTORY = __DIR__ . '/../Fixtures/Make/Caches';

    public function testAnOptionWithAValueIsWeighedAsAnArgument(): void
    {
        $cachesDirectory = $this->getFixtureCopy(self::CACHES_DIRECTORY);

        $this->writeCaches($cachesDirectory, ['first']);

        $result = $this->runMake(['cc', '--', '--first=x'], directory: $cachesDirectory, doExpectFailure: true);

        self::assertStringContainsString('No cache named first=x', $result);
        self::assertDirectoryExists($cachesDirectory . '/.cache/first');
    }

    public function testCcRemovesTheCachesItIsGivenAndNothingElse(): void
    {
        $cachesDirectory = $this->getFixtureCopy(self::CACHES_DIRECTORY);

        $this->writeCaches($cachesDirectory, ['first', 'second']);

        $removed = $this->runMake(['cc', 'first'], directory: $cachesDirectory);

        self::assertStringContainsString('Removed ./.cache/first', $removed);
        self::assertDirectoryDoesNotExist($cachesDirectory . '/.cache/first');
        self::assertDirectoryExists($cachesDirectory . '/.cache/second');
    }

    public function testCcTakesAToolNameAndNeverRunsTheTargetItNames(): void
    {
        $cachesDirectory = $this->getFixtureCopy(self::CACHES_DIRECTORY);

        $this->writeCaches($cachesDirectory, ['help.cache', 'other']);

        $removed = $this->runMake(['cc', 'help'], directory: $cachesDirectory);

        self::assertStringContainsString('Removed ./.cache/help.cache', $removed);
        self::assertStringNotContainsString('Usage:', $removed);
        self::assertDirectoryExists($cachesDirectory . '/.cache/other');
    }

    public function testCcRefusesACacheNameTheProjectDoesNotHave(): void
    {
        $cachesDirectory = $this->getFixtureCopy(self::CACHES_DIRECTORY);

        $this->writeCaches($cachesDirectory, ['first']);

        $result = $this->runMake(['cc', 'nope'], directory: $cachesDirectory, doExpectFailure: true);

        self::assertStringContainsString('No cache named nope', $result);
        self::assertStringContainsString('Try first', $result);
        self::assertDirectoryExists($cachesDirectory . '/.cache/first');
    }

    public function testAMistypedArgumentIsScoredAgainstWhatTheTargetAccepts(): void
    {
        $cachesDirectory = $this->getFixtureCopy(self::CACHES_DIRECTORY);

        $this->writeCaches($cachesDirectory, ['phpstan.cache']);

        $result = $this->runMake(['cc', 'phpstna.cache'], directory: $cachesDirectory, doExpectFailure: true);

        self::assertStringContainsString('No cache named phpstna.cache', $result);
        self::assertStringContainsString('Did you mean phpstan.cache', $result);
        self::assertStringNotContainsString('Try ', $result);
        self::assertStringNotContainsString('Removed', $result);
        self::assertDirectoryExists($cachesDirectory . '/.cache/phpstan.cache');
    }

    public function testASuggestionOneLetterOffStandsAlone(): void
    {
        $cachesDirectory = $this->getFixtureCopy(self::CACHES_DIRECTORY);

        $this->writeCaches($cachesDirectory, ['eslint', 'eslint-group', 'eslint-print']);

        $result = $this->runMake(['cc', 'eslin'], directory: $cachesDirectory, doExpectFailure: true);

        self::assertStringContainsString('Did you mean eslint?', $result);
        self::assertStringNotContainsString('eslint-group', $result);
        self::assertStringNotContainsString('eslint-print', $result);
    }

    public function testCcNamesACacheWithoutRunningItsName(): void
    {
        $cachesDirectory = $this->getFixtureCopy(self::CACHES_DIRECTORY);

        $this->writeCaches($cachesDirectory, [
            '$(id)',
            'it\'s',
            "x\e]0;title\x07y",
        ]);

        $listed  = $this->runMake(['cc'], directory: $cachesDirectory);
        $refused = $this->runMake(['cc', 'nope'], directory: $cachesDirectory, doExpectFailure: true);

        self::assertStringContainsString('$(id), it\'s or x?]0;title?y', $listed);
        self::assertStringContainsString('Try $(id), it\'s or x?]0;title?y', $refused);
    }

    public function testALinkedPathIsCheckedWithoutRunningIt(): void
    {
        $cachesDirectory = $this->getFixtureCopy(self::CACHES_DIRECTORY);

        $this->writeCaches($cachesDirectory, ['x;touch${IFS}hyperlink-ran;.d']);

        $this->runMake(['cc', 'x;touch${IFS}hyperlink-ran;.d'], [
            'EDITOR' => 'vscode',
            ...self::COLORED_ENV,
        ], $cachesDirectory);

        self::assertFileDoesNotExist($cachesDirectory . '/hyperlink-ran');
    }

    #[Group('tty')]
    public function testAConfirmationWhoseInputIsNoTerminalTakesTheDefault(): void
    {
        $cachesDirectory = $this->getFixtureCopy(self::CACHES_DIRECTORY);

        $this->writeCaches($cachesDirectory, ['first']);

        $this->runMakeOnATty(['cc', '</dev/null'], $cachesDirectory, "y\n");

        self::assertDirectoryExists($cachesDirectory . '/.cache/first');
    }

    public function testCcKeepsEveryCacheWhereNobodyCanAnswer(): void
    {
        $cachesDirectory = $this->getFixtureCopy(self::CACHES_DIRECTORY);

        $this->writeCaches($cachesDirectory, ['first', 'second']);

        $kept = $this->runMake(['cc'], ['CI' => '1'], directory: $cachesDirectory);

        self::assertStringContainsString('This removes every cache', $kept);
        self::assertStringContainsString('Nothing removed', $kept);
        self::assertDirectoryExists($cachesDirectory . '/.cache');
    }

    public function testCcSaysSoWhenThereIsNothingToRemove(): void
    {
        $cachesDirectory = $this->getFixtureCopy(self::CACHES_DIRECTORY);

        self::assertStringContainsString(
            'No caches to remove',
            $this->runMake(['cc'], directory: $cachesDirectory),
        );
    }

    public function testCcReportsNoRemovalForACacheThatWasNeverThere(): void
    {
        $cachesDirectory = $this->getFixtureCopy(self::CACHES_DIRECTORY);
        $result          = $this->runMake(['cc', 'nope'], directory: $cachesDirectory);

        self::assertStringContainsString('No caches to remove', $result);
        self::assertStringNotContainsString('Removed', $result);
    }

    /**
     * @param list<string> $names
     */
    private function writeCaches(string $cachesDirectory, array $names): void
    {
        new Filesystem()->mkdir(array_map(
            static fn (string $name): string => $cachesDirectory . '/.cache/' . $name,
            $names,
        ));
    }
}
