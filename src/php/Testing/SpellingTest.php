<?php

declare(strict_types=1);

namespace Brnshkr\Config\Testing;

use Brnshkr\Config\Spelling;
use JsonException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function getcwd;
use function implode;
use function sprintf;

/**
 * Holds a repository's prose, docblocks and identifiers to American English.
 *
 * @internal
 *
 * @no-named-arguments
 */
#[CoversNothing]
#[Group('spelling')]
final class SpellingTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws RuntimeException
     */
    public function testEveryTrackedFileIsWrittenInAmericanEnglish(): void
    {
        $root = getcwd();

        self::assertNotFalse($root, 'The working directory has to be readable to scan a repository.');

        $findings = [];

        foreach (Spelling::scan($root) as $finding) {
            $findings[] = sprintf(
                '%s:%d — "%s", use "%s"',
                $finding['path'],
                $finding['line'],
                $finding['word'],
                $finding['suggestion'],
            );
        }

        self::assertSame([], $findings, "British spellings found:\n" . implode("\n", $findings));
    }
}
