<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[CoversNothing]
final class ArgumentsTest extends TestCase
{
    use MakeTrait;

    public function testAProjectNameTheRecipesCannotQuoteIsRefused(): void
    {
        $result = $this->runMake(['help'], directory: $this->writeProject('it\'s'), doExpectFailure: true);

        self::assertStringContainsString('holds a character the recipes cannot quote', $result);
    }

    public function testAPackageNamedForTheParentDirectoryIsRefused(): void
    {
        $result = $this->runMake(
            ['help'],
            directory: $this->writeProject('ParentName', '{"name": "vendor/.."}'),
            doExpectFailure: true,
        );

        self::assertStringContainsString('`vendor/..` cannot name a package', $result);
    }

    public function testACollidingNameStaysWithTheProjectAndTheSharedOneMovesAside(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Collision';

        self::assertStringContainsString('collision', $this->runMake(['fixtures'], directory: $directory));
        self::assertContainsSymbol('brnshkr-fixtures', $this->runMake(['help', 'v'], directory: $directory));
    }

    public function testACollisionInsideAChosenNamespaceIsRefused(): void
    {
        $result = $this->runMake(
            ['shared-fixtures'],
            directory: __DIR__ . '/../Fixtures/Make/PrefixedCollision',
            doExpectFailure: true,
        );

        self::assertStringContainsString('shared-fixtures is defined in', $result);
        self::assertStringContainsString('TARGET_PREFIX', $result);
    }

    public function testTargetPrefixNamespacesOnlyTheSharedTargets(): void
    {
        $result = $this->runMake(['shared-help', 'TARGET_PREFIX=shared'], directory: self::CONSUMER_DIRECTORY);

        self::assertContainsSymbol('shared-help', $result);
        self::assertContainsSymbol('consumer-command', $result);
        self::assertDoesNotContainSymbol('shared-consumer-command', $result);
    }

    public function testPhonyKnobLimitsMarkingToTheSharedTargets(): void
    {
        $all    = $this->runMake(['-p'], directory: self::CONSUMER_DIRECTORY);
        $shared = $this->runMake(['-p', 'PHONY=shared'], directory: self::CONSUMER_DIRECTORY);
        $none   = $this->runMake(['-p', 'PHONY=0'], directory: self::CONSUMER_DIRECTORY);

        self::assertMatchesRegularExpression('/^\.PHONY:.* consumer-command /m', $all);
        self::assertMatchesRegularExpression('/^\.PHONY:(?!.* consumer-command ).* help /m', $shared);
        self::assertMatchesRegularExpression('/^\.PHONY:\s*$/m', $none);
    }

    public function testArgumentsReachTheTargetTheyFollow(): void
    {
        $alone = $this->runMake(['consumer-arguments'], directory: self::CONSUMER_DIRECTORY);

        $shared = $this->runMake(
            ['before', 'consumer-arguments', 'own', 'consumer-arguments-second'],
            directory: self::CONSUMER_DIRECTORY,
        );

        $dashed = $this->runMake(['consumer-arguments', '--', '-x'], directory: self::CONSUMER_DIRECTORY);

        self::assertStringContainsString('consumer-arguments [] []', $alone);
        self::assertStringContainsString('consumer-arguments [\'own\' \'before\'] [\'own\']', $shared);
        self::assertStringContainsString('consumer-arguments-second [\'before\'] [\'before\']', $shared);
        self::assertStringContainsString('consumer-arguments [\'-x\'] [\'-x\']', $dashed);
    }

    public function testAnArgumentReachesTheTargetAsWritten(): void
    {
        $result = $this->runMake(
            ['consumer-arguments', '--', '--filter', 'A|B', 'c$HOME'],
            directory: self::CONSUMER_DIRECTORY,
        );

        self::assertStringContainsString('consumer-arguments [\'--filter\' \'A|B\'', $result);
        self::assertStringNotContainsString('cOME', $result);
    }

    public function testAnOptionWithAValueReachesTheTargetItFollows(): void
    {
        $result = $this->runMake(
            ['--', '--shared=1', 'consumer-arguments', '--own=2', 'consumer-arguments-second', '--second=$HOME'],
            directory: self::CONSUMER_DIRECTORY,
        );

        self::assertStringContainsString('consumer-arguments [\'--own=2\' \'--shared=1\']', $result);
        self::assertStringContainsString('consumer-arguments-second [\'--second=', $result);
        self::assertStringContainsString('\' \'--shared=1\'] [\'--second=', $result);
        self::assertStringNotContainsString('--second=OME', $result);
    }

    public function testAFileArgumentRunsWithoutMakeReportingIt(): void
    {
        $result = $this->runMake(['consumer-arguments', '.', '../Consumer'], directory: self::CONSUMER_DIRECTORY);

        self::assertStringContainsString('consumer-arguments [\'.\' \'../Consumer\']', $result);
        self::assertStringNotContainsString('is up to date', $result);
    }

    public function testASubcommandTheArgumentsNameReplacesTheTargetsOwn(): void
    {
        $environment = ['PATH' => self::TOOLS_DIRECTORY . '/bin:' . Str::fromEnvironment('PATH')];

        $phpStan = $this->runMake(
            ['-n', 'phpstan', '_COMMAND=', '--', 'clear-result-cache', '--level', '5', 'src', '--error-format=json'],
            $environment,
            self::TOOLS_DIRECTORY,
        );

        $rector = $this->runMake(
            ['-n', 'rector', '_COMMAND=', '--', 'process', '--dry-run', '--output-format=json', 'src'],
            $environment,
            self::TOOLS_DIRECTORY,
        );

        $ownLevel = $this->runMake(['-n', 'phpstan', '--', '--level', '5'], $environment, self::TOOLS_DIRECTORY);

        self::assertStringContainsString(
            '\'clear-result-cache\' \'src\' \'--error-format=json\' --configuration',
            $phpStan,
        );

        self::assertStringNotContainsString('analyze', $phpStan);
        self::assertStringContainsString('\'process\' \'--output-format=json\' \'src\' --config', $rector);
        self::assertStringNotContainsString('rector process', $rector);
        self::assertStringContainsString('--dry-run', $rector);
        self::assertStringContainsString('phpstan analyze \'--level\' \'5\' --configuration', $ownLevel);
    }

    public function testUnknownCommandIsReported(): void
    {
        $result = $this->runMake(['no-such-command'], directory: self::CONSUMER_DIRECTORY, doExpectFailure: true);

        self::assertStringContainsString('Unknown command', $result);
        self::assertStringContainsString('no-such-command', $result);
        self::assertStringContainsString('to see available commands', $result);
    }

    public function testAProjectTargetAfterANamingVerbIsRefusedBeforeAnythingRuns(): void
    {
        $result = $this->runMake(
            ['cc', 'fixtures'],
            directory: __DIR__ . '/../Fixtures/Make/Collision',
            doExpectFailure: true,
        );

        self::assertStringContainsString('fixtures is a target of this project, so it cannot follow cc', $result);
        self::assertDoesNotMatchRegularExpression('/^collision$/m', $result);
    }

    public function testANameTheProjectDefinesInTwoOfItsOwnFilesIsRefused(): void
    {
        $result = $this->runMake(
            ['twice'],
            directory: __DIR__ . '/../Fixtures/Make/Duplicates',
            doExpectFailure: true,
        );

        self::assertStringContainsString('twice is defined in', $result);
        self::assertStringContainsString('again.mk', $result);
        self::assertStringContainsString('defines the same name twice', $result);
    }

    private function writeProject(string $name, ?string $manifest = null): string
    {
        $directory  = $this->createFixtureRoot() . '/' . $name;
        $filesystem = new Filesystem();

        $filesystem->dumpFile($directory . '/Makefile', "include ../conf/Makefile\n");

        if ($manifest !== null) {
            $filesystem->dumpFile($directory . '/package.json', $manifest);
        }

        return $directory;
    }
}
