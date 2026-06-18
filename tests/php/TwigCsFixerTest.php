<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Json;
use Brnshkr\Config\Str;
use Brnshkr\Config\TwigCsFixer;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use TwigCsFixer\Environment\StubbedEnvironment;
use TwigCsFixer\Report\Report;
use TwigCsFixer\Report\Violation;
use TwigCsFixer\Runner\Linter;
use TwigCsFixer\Token\Tokenizer;

use function array_map;
use function chdir;
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

    /**
     * @throws DirectoryNotFoundException
     * @throws JsonException
     */
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

    /**
     * @throws DirectoryNotFoundException
     */
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
