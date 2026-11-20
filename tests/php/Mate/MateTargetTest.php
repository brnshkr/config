<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Mate;

use Brnshkr\Config\Json;
use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
#[CoversNothing]
final class MateTargetTest extends TestCase
{
    public function testAMessageReachesTheToolWithItsQuotesAndNewlinesAndTheAnswerIsToon(): void
    {
        $result = Project::runTarget('mate', [
            'ARGS' => "tools:call brnshkr-commitlint-check \"--message=fix(make): quote it's names\n\nkeep it's body\"",
        ]);

        self::assertStringStartsWith('exitCode: 1', $result['output']);
        self::assertStringContainsString('fix(make): quote it\'s names\n\nkeep it\'s body', $result['output']);
    }

    public function testTheToonCommandsAreTheOnesWhoseFormatOffersToon(): void
    {
        /**
         * @var array{
         *     commands: list<array{
         *         name: string,
         *         definition: array{
         *             options: array<string, array{
         *                 description: string,
         *             }>,
         *         },
         *     }>,
         * } $mateList
         */
        $mateList         = Json::decode(self::runMake(['mate', 'ARGS=list --format=json']));
        $resolvedHelp     = self::runMake(['help', 'resolve', 'vv', 'VALUE_WIDTH=2000']);
        $toonCommandNames = [];

        foreach ($mateList['commands'] as $mateCommand) {
            if (Str::contains($mateCommand['definition']['options']['format']['description'] ?? '', 'toon')) {
                $toonCommandNames[] = $mateCommand['name'];
            }
        }

        self::assertSame(
            $toonCommandNames,
            Str::split(Str::trim(Str::match($resolvedHelp, '/^ *MATE_TOON_COMMANDS +\?= +(?<commands>[^\n(]+)/m')['commands'] ?? ''), ' '),
        );
    }

    public function testAToolRunsTheTargetsOfAPrefixedProject(): void
    {
        $result = self::runMake(['acme-mate', 'ARGS=tools:call brnshkr-quality-check --tool=acme'], ['TARGET_PREFIX' => 'acme']);

        self::assertStringContainsString('Unknown tool `acme`.', $result);
    }

    /**
     * @param list<string> $arguments
     * @param array<non-empty-string, string> $environment
     */
    private static function runMake(array $arguments, array $environment = []): string
    {
        $process = new Process(['make', 'NO_COLOR=1', ...$arguments], Project::getRootDirectory(), [
            'MAKEFLAGS' => false,
            'MAKELEVEL' => false,
            'MFLAGS'    => false,
            ...$environment,
        ]);

        $process->mustRun();

        return $process->getOutput();
    }
}
