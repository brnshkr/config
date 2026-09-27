<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Mate;

use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Mate\Tool\TestTool;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

use function is_file;
use function sprintf;
use function unlink;

/**
 * @internal
 */
#[CoversNothing]
final class TestToolTest extends TestCase
{
    private const string MARKER_PATH          = __DIR__ . '/../../../.cache/test-tool-filter-ran';
    private const string NESTED_SNAPSHOT_PATH = 'tests/php/Make/__snapshots__/TestToolTest__probe.txt';

    #[After]
    public function removeTheMarker(): void
    {
        if (is_file(self::MARKER_PATH)) {
            unlink(self::MARKER_PATH);
        }
    }

    #[After]
    public function removeTheNestedSnapshot(): void
    {
        new Filesystem()->remove(Project::getRootDirectory() . '/' . self::NESTED_SNAPSHOT_PATH);
    }

    public function testASnapshotInANestedSnapshotDirectoryIsReported(): void
    {
        new Filesystem()->dumpFile(Project::getRootDirectory() . '/' . self::NESTED_SNAPSHOT_PATH, '');

        $result = new TestTool()->runTests('php', 'noSuchTestMatchesThisFilter');

        self::assertStringContainsString(self::NESTED_SNAPSHOT_PATH, $result);
    }

    public function testAFilterReachesTheRunnerAsOneArgument(): void
    {
        new TestTool()->runTests('js', sprintf('no-such-test; touch %s', self::MARKER_PATH));

        self::assertFileDoesNotExist(self::MARKER_PATH);
    }
}
