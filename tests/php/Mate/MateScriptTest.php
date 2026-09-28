<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Mate;

use Brnshkr\Config\Mate\Support\Project;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class MateScriptTest extends TestCase
{
    public function testAMessageReachesTheToolWithItsQuotesAndNewlinesAndTheAnswerIsToon(): void
    {
        $result = Project::run([
            Project::getRootDirectory() . '/scripts/mate.php',
            'tools:call',
            'project-commitlint-check',
            "--message=fix(make): quote it's names\n\nkeep it's body",
        ]);

        self::assertStringStartsWith('exitCode: 2', $result['output']);
        self::assertStringContainsString('fix(make): quote it\'s names\n\nkeep it\'s body', $result['output']);
    }
}
