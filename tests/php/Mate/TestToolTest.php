<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Mate;

use Brnshkr\Config\Mate\Tool\TestTool;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

use function is_file;
use function sprintf;
use function unlink;

/**
 * @internal
 */
#[CoversNothing]
final class TestToolTest extends TestCase
{
    private const string MARKER_PATH = __DIR__ . '/../../../.cache/test-tool-filter-ran';

    #[After]
    public function removeTheMarker(): void
    {
        if (is_file(self::MARKER_PATH)) {
            unlink(self::MARKER_PATH);
        }
    }

    public function testAFilterReachesTheRunnerAsOneArgument(): void
    {
        new TestTool()->runTests('js', sprintf('no-such-test; touch %s', self::MARKER_PATH));

        self::assertFileDoesNotExist(self::MARKER_PATH);
    }
}
