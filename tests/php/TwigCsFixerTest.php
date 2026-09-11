<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Json;
use Brnshkr\Config\Str;
use Brnshkr\Config\TwigCsFixer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use TwigCsFixer\Environment\StubbedEnvironment;
use TwigCsFixer\Report\Report;
use TwigCsFixer\Report\Violation;
use TwigCsFixer\Rules\Delimiter\EndBlockNameRule;
use TwigCsFixer\Rules\File\DirectoryNameRule;
use TwigCsFixer\Rules\File\FileExtensionRule;
use TwigCsFixer\Rules\Node\NodeRuleInterface;
use TwigCsFixer\Rules\RuleInterface;
use TwigCsFixer\Runner\Linter;
use TwigCsFixer\Token\Tokenizer;

use function array_filter;
use function array_map;
use function chdir;
use function count;
use function getcwd;
use function usort;

/**
 * @internal
 */
#[CoversClass(TwigCsFixer::class)]
final class TwigCsFixerTest extends TestCase
{
    use MatchesSnapshots;

    private const string PROJECT_DIRECTORY = __DIR__ . '/Fixtures/TwigCsFixer/project';

    public function testFlagsExpectedNameViolations(): void
    {
        $report = $this->lintProject();

        $violations = array_map(
            static fn (Violation $violation): array => [
                'file'    => Str::trim($violation->getFilename(), './', 'start'),
                'rule'    => $violation->getRuleName(),
                'level'   => Violation::getLevelAsString($violation->getLevel()),
                'message' => $violation->getMessage(),
            ],
            $report->getViolations(),
        );

        usort(
            $violations,
            static fn (array $left, array $right): int => [$left['file'], $left['message']] <=> [$right['file'], $right['message']],
        );

        $this->assertMatchesJsonSnapshot(Json::encode($violations));
    }

    public function testRulesAreAddedToTheStandardsRuleset(): void
    {
        $baseline = TwigCsFixer::getConfig()->getRuleset()->getRules();

        $rules = TwigCsFixer::getBuilder()
            ->addRules([new EndBlockNameRule()])
            ->build()
            ->getRuleset()
            ->getRules()
        ;

        self::assertCount(count($baseline) + 1, $rules);
    }

    public function testFromAddsToAConfigAnotherFileBuilt(): void
    {
        $config = TwigCsFixer::getConfig();

        $rules = TwigCsFixer::from($config)
            ->addRules([new EndBlockNameRule()])
            ->build()
            ->getRuleset()
            ->getRules()
        ;

        self::assertCount(count($config->getRuleset()->getRules()), $rules);
    }

    public function testSetRulesReplacesTheStandardsRuleset(): void
    {
        $ruleset = TwigCsFixer::getBuilder()
            ->setRules([new FileExtensionRule()])
            ->build()
            ->getRuleset()
        ;

        $rules = array_map(
            static fn (RuleInterface|NodeRuleInterface $rule): string => $rule::class,
            $ruleset->getRules(),
        );

        self::assertSame([FileExtensionRule::class], $rules);
    }

    public function testRulesAreRemovedByClassName(): void
    {
        $rules     = TwigCsFixer::getConfig()->getRuleset()->getRules();
        $firstRule = $rules[0] ?? null;

        self::assertNotNull($firstRule);
        self::assertCount(count($rules) - 1, TwigCsFixer::getBuilder()->removeRules([$firstRule::class])->build()->getRuleset()->getRules());
    }

    public function testRulesAreOverriddenByClass(): void
    {
        $configured = array_filter(
            TwigCsFixer::getConfig()->getRuleset()->getRules(),
            static fn (object $rule): bool => $rule instanceof DirectoryNameRule,
        );

        self::assertGreaterThan(1, count($configured));

        $ruleset = TwigCsFixer::getBuilder()
            ->overrideRules([new DirectoryNameRule()])
            ->build()
            ->getRuleset()
        ;

        $overridden = array_filter(
            $ruleset->getRules(),
            static fn (object $rule): bool => $rule instanceof DirectoryNameRule,
        );

        self::assertCount(1, $overridden);
    }

    private function lintProject(): Report
    {
        $previousDirectory = getcwd() ?: '.';

        chdir(self::PROJECT_DIRECTORY);

        try {
            $config = TwigCsFixer::getConfig();

            $stubbedEnvironment = new StubbedEnvironment(
                $config->getTwigExtensions(),
                $config->getTokenParsers(),
                $config->getNodeVisitors(),
            );

            return new Linter($stubbedEnvironment, new Tokenizer($stubbedEnvironment))->run(
                $config->getFinder(),
                $config->getRuleset(),
            );
        } finally {
            chdir($previousDirectory);
        }
    }
}
