<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class VerbsTest extends TestCase
{
    use MakeTrait;

    public function testFixWritesCheckOnlyReadsAndTestOnlyTests(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';
        $fix       = $this->runMake(['fix'], directory: $directory);
        $check     = $this->runMake(['check'], directory: $directory);
        $test      = $this->runMake(['test'], directory: $directory);

        self::assertStringContainsString('php-cs-fixer fix', $fix);
        self::assertStringNotContainsString('--dry-run', $fix);
        self::assertStringNotContainsString('phpstan', $fix);
        self::assertStringNotContainsString('phpunit', $fix);
        self::assertStringContainsString('--dry-run', $check);
        self::assertStringContainsString('phpstan analyze', $check);
        self::assertStringNotContainsString('phpunit', $check);
        self::assertStringContainsString('phpunit', $test);
        self::assertStringNotContainsString('php-cs-fixer', $test);
        self::assertStringNotContainsString('phpstan', $test);
    }

    public function testAProjectAddsItsOwnFixerAnalyzerGroupAndTest(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Extras';

        self::assertStringContainsString('own analyzer', $this->runMake(['check'], directory: $directory));
        self::assertStringContainsString('own fixer', $this->runMake(['fix'], directory: $directory));
        self::assertStringContainsString('own group', $this->runMake(['group'], directory: $directory));
        self::assertStringContainsString('own test', $this->runMake(['test'], directory: $directory));
    }

    public function testCiRunsItsTargetsInTheOrderTheyAreListed(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';
        $default   = $this->runMake(['ci'], directory: $directory);
        $fixing    = $this->runMake(['ci'], ['CI_TARGETS' => 'fix check test'], $directory);

        self::assertMatchesRegularExpression('/--dry-run\n.*phpstan analyze.*\n.*phpunit/s', $default);
        self::assertStringNotContainsString(" -v\n", $default);
        self::assertMatchesRegularExpression('/php-cs-fixer fix [^\n]* -v\n.*--dry-run\n.*phpunit/s', $fixing);
    }

    public function testFixRunsRectorBeforePhpCsFixerEvenInParallel(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';

        foreach ([[], ['-j4']] as $flags) {
            $fix = $this->runMake([...$flags, 'fix'], directory: $directory);

            self::assertMatchesRegularExpression('/rector done\n(?:.*\n)*php-cs-fixer fix/', $fix);
            self::assertStringNotContainsString('warning', $fix);
        }
    }

    public function testSnapshotsAreUpdatedThroughTheRunnersOwnMechanism(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';
        $phpunit   = $this->runMake(['phpunit-update'], directory: $directory);
        $pest      = $this->runMake(['phpunit-update'], ['PHP_UNIT' => 'echo pest'], $directory);

        self::assertStringContainsString('update-snapshots --configuration', $phpunit);
        self::assertStringContainsString('--do-not-fail-on-incomplete', $phpunit);
        self::assertStringNotContainsString('-d --update-snapshots', $phpunit);
        self::assertStringContainsString('--update-snapshots --do-not-fail-on-incomplete', $pest);
    }

    public function testAVerbAnnouncesEachTargetItRunsAndNothingElseDoes(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';
        $check     = $this->runMake(['check'], directory: $directory);
        $fix       = $this->runMake(['fix'], directory: $directory);
        $ci        = $this->runMake(['ci'], directory: $directory);
        $single    = $this->runMake(['rector-dry-run'], directory: $directory);

        self::assertStringContainsString("[acme/verbs] Running `rector-dry-run`\nrector process", $check);
        self::assertStringContainsString("[acme/verbs] Running `phpstan`\nphpstan analyze", $check);
        self::assertStringNotContainsString("Running `rector`\n", $check);
        self::assertStringContainsString("[acme/verbs] Running `rector`\nrector process", $fix);
        self::assertStringNotContainsString('Running `_', $fix);
        self::assertStringContainsString("[acme/verbs] Running `check`\n", $ci);
        self::assertStringContainsString("[acme/verbs] Running `test`\n", $ci);
        self::assertStringNotContainsString('Running', $single);
    }

    public function testAnAnnouncementCanBeRewordedOrSilenced(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';
        $reworded  = $this->runMake(['check'], ['ANNOUNCEMENT' => '>>> %s'], $directory);
        $silenced  = $this->runMake(['check'], ['ANNOUNCEMENT' => ''], $directory);

        self::assertStringContainsString("[acme/verbs] >>> phpstan\n", $reworded);
        self::assertStringNotContainsString('[acme/verbs]', $silenced);
    }

    public function testAVerbRunsEveryToolPastAFailureWhileCiAndAFixChainStop(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Verbs';
        $check     = $this->runMake(['check'], ['PHP_CS_FIXER' => 'false'], $directory, doExpectFailure: true);
        $ci        = $this->runMake(['ci'], ['PHP_CS_FIXER' => 'false'], $directory, doExpectFailure: true);
        $fix       = $this->runMake(['fix'], ['RECTOR' => 'false'], $directory, doExpectFailure: true);

        self::assertStringContainsString('phpstan done', $check);
        self::assertStringContainsString('phpstan done', $ci);
        self::assertStringNotContainsString('Running `test`', $ci);
        self::assertStringNotContainsString('php-cs-fixer fix', $fix);
    }

    public function testAParallelCheckPrintsEachToolWhole(): void
    {
        $check = $this->runMake(['-j4', 'check'], directory: __DIR__ . '/../Fixtures/Make/Verbs');

        self::assertMatchesRegularExpression(
            '/Running `php-cs-fixer-dry-run`\nphp-cs-fixer fix [^\n]*--dry-run\nphp-cs-fixer done/',
            $check,
        );

        self::assertMatchesRegularExpression(
            '/Running `rector-dry-run`\nrector process [^\n]*--dry-run\nrector done/',
            $check,
        );

        self::assertMatchesRegularExpression('/Running `phpstan`\nphpstan analyze [^\n]*\nphpstan done/', $check);
    }

    public function testFindingsAreCountedByTheIdentifierTheyCarry(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Group';
        $pairs     = $this->runMake(['group-pairs'], directory: $directory);
        $lists     = $this->runMake(['group-lists'], directory: $directory);

        self::assertStringContainsString('2  acme.first   First finding.', $pairs);
        self::assertStringContainsString('1  acme.second  Second finding.', $pairs);
        self::assertStringContainsString('2  acme_second', $lists);
        self::assertStringNotContainsString('acme_second ', $lists);
    }

    public function testFindingsAreCountedByTheCodeACompilerPrints(): void
    {
        $text = $this->runMake(['group-text'], directory: __DIR__ . '/../Fixtures/Make/Group');

        self::assertStringContainsString('2  TS2345 ', $text);
        self::assertStringContainsString('1  TS2571 ', $text);
        self::assertStringContainsString('1  MD013/line-length  Line length', $text);
        self::assertStringContainsString('Argument of type \'string\'', $text);
    }

    public function testSuppressedFindingsAreNotCounted(): void
    {
        $eslint = $this->runMake(['group-eslint'], directory: __DIR__ . '/../Fixtures/Make/Group');

        self::assertStringContainsString('2  acme/real  Real finding.', $eslint);
        self::assertStringNotContainsString('acme/suppressed', $eslint);
    }

    public function testAMessageContainingBracesKeepsItsText(): void
    {
        $braces = $this->runMake(['group-braces'], directory: __DIR__ . '/../Fixtures/Make/Group');

        self::assertStringContainsString('2  acme.shape  Offset \'x\' does not exist on array{a: int}.', $braces);
        self::assertStringContainsString("✘ 2 findings\n", $braces);
    }

    public function testAToolSaysHowManyFindingsItHasOrThatItHasNone(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Group';

        self::assertStringContainsString("✘ 3 findings\n", $this->runMake(['group-pairs'], directory: $directory));
        self::assertStringContainsString("✘ 1 finding\n", $this->runMake(['group-single'], directory: $directory));
        self::assertStringContainsString("✔ No findings\n", $this->runMake(['group-clean'], directory: $directory));

        self::assertStringContainsString(
            "9  acme.frequent  Frequent finding.\n1  acme.rare      Rare finding.\n✘ 10 findings\n",
            $this->runMake(['group-wide'], directory: $directory),
        );
    }

    public function testAGroupIsLabeledOnlyWhenAVerbRunsIt(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Group';
        $verb      = $this->runMake(['groups'], directory: $directory);
        $silenced  = $this->runMake(['groups'], ['ANNOUNCEMENT' => ''], $directory);

        self::assertStringContainsString("[Group] Running `group-pairs`\n  2  acme.first", $verb);
        self::assertStringNotContainsString('Running…', $verb);
        self::assertStringNotContainsString('[Group]', $silenced);
    }

    public function testParallelGroupsPrintEachReportWhole(): void
    {
        $output = $this->runMake(['-j2', 'groups'], directory: __DIR__ . '/../Fixtures/Make/Group');

        self::assertStringContainsString(
            "[Group] Running `group-single`\n  1  acme.only  Only finding.\n  ✘ 1 finding\n",
            $output,
        );

        self::assertStringNotContainsString('Running…', $output);

        self::assertStringContainsString(
            "[Group] Running `group-pairs`\n  2  acme.first   First finding.\n"
            . "  1  acme.second  Second finding.\n  ✘ 3 findings\n",
            $output,
        );
    }

    public function testOnlyAToolThatReportedNothingFailsTheTarget(): void
    {
        $directory = __DIR__ . '/../Fixtures/Make/Group';
        $failing   = $this->runMake(['group-failing'], directory: $directory);
        $crashed   = $this->runMake(['group-crash'], directory: $directory, doExpectFailure: true);

        self::assertStringContainsString("✘ 3 findings\n", $failing);
        self::assertStringNotContainsString('No findings', $crashed);
    }
}
