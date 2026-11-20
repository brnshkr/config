<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Mate;

use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Mate\Tool\TestTool;
use Brnshkr\Config\Tests\Trait\TemporaryRootTrait;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

use function sprintf;

/**
 * @internal
 */
#[CoversNothing]
final class TestToolTest extends TestCase
{
    use TemporaryRootTrait;

    private const string TEMPORARY_ROOT_PREFIX = 'brnshkr-test-tool-';
    private const string NESTED_SNAPSHOT_PATH  = 'tests/php/Make/__snapshots__/TestToolTest__probe.txt';
    private const string FAILING_PHP_PATH      = 'tests/php/Mate/TestToolFailingTest.php';
    private const string FAILING_JS_PATH       = 'tests/js/test-tool-failing.test.ts';

    #[After]
    public function removeTheNestedSnapshot(): void
    {
        new Filesystem()->remove(Project::getRootDirectory() . '/' . self::NESTED_SNAPSHOT_PATH);
    }

    #[After]
    public function removeTheFailingTests(): void
    {
        new Filesystem()->remove([
            Project::getRootDirectory() . '/' . self::FAILING_PHP_PATH,
            Project::getRootDirectory() . '/' . self::FAILING_JS_PATH,
        ]);
    }

    public function testASnapshotChangedBeforeTheRunIsListedAsUnchanged(): void
    {
        new Filesystem()->dumpFile(Project::getRootDirectory() . '/' . self::NESTED_SNAPSHOT_PATH, '');

        $result = new TestTool()->runTests('php', 'noSuchTestMatchesThisFilter');

        self::assertStringNotContainsString('changedSnapshots', $result);
        self::assertStringContainsString(self::NESTED_SNAPSHOT_PATH, $result);
    }

    public function testAFilterReachesTheRunnerAsOneArgument(): void
    {
        $markerPath = $this->createMarkerPath(self::TEMPORARY_ROOT_PREFIX);
        $result     = new TestTool()->runTests('js', sprintf('no-such-test; touch %s', $markerPath));

        self::assertFileDoesNotExist($markerPath);
        self::assertStringStartsWith('exitCode: 1', $result);
    }

    public function testAFailingPhpTestIsAnsweredAsARow(): void
    {
        new Filesystem()->dumpFile(
            Project::getRootDirectory() . '/' . self::FAILING_PHP_PATH,
            "<?php\n\nnamespace Brnshkr\\Config\\Tests\\Mate;\n\nuse PHPUnit\\Framework\\TestCase;\n\n"
            . "final class TestToolFailingTest extends TestCase\n{\n    public function testFails(): void\n    {\n"
            . "        self::assertSame('expected', 'actual');\n    }\n}\n",
        );

        self::assertStringContainsString(
            "failures[1]{test,file,line,message}:\n  \"Brnshkr\\\\Config\\\\Tests\\\\Mate\\\\TestToolFailingTest::Fails\","
            . self::FAILING_PHP_PATH . ',11,Failed asserting that two strings are identical.',
            new TestTool()->runTests('php', 'TestToolFailingTest'),
        );
    }

    public function testAFailingJsTestIsAnsweredAsARow(): void
    {
        new Filesystem()->dumpFile(
            Project::getRootDirectory() . '/' . self::FAILING_JS_PATH,
            "import { expect, test } from 'vitest';\n\ntest('acme fails', () => {\n  expect('actual').toBe('expected');\n});\n",
        );

        self::assertStringContainsString(
            "failures[1]{test,file,line,message}:\n  acme fails," . self::FAILING_JS_PATH . ',4,',
            new TestTool()->runTests('js', 'test-tool-failing'),
        );
    }
}
