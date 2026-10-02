<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Make\Trait\MakeTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

use function sprintf;

/**
 * @internal
 */
#[CoversNothing]
final class PackTest extends TestCase
{
    use MakeTrait;
    use MatchesSnapshots;

    private const string MANIFEST_DIRECTORY = __DIR__ . '/../Fixtures/Make/Manifest';
    private const string PACK_DIRECTORY     = __DIR__ . '/../Fixtures/Make/Pack';

    public function testAManifestOpeningOnTheNameLineStillNamesThePackage(): void
    {
        $resolved = $this->runMake(['help', 'resolve', 'vv'], directory: self::MANIFEST_DIRECTORY);

        self::assertMatchesRegularExpression('/PACKAGE\s+\?=\s+one-line-manifest/', $resolved);
        self::assertMatchesRegularExpression('/VENDOR\s+\?=\s+@acme/', $resolved);
    }

    public function testTheListTargetsNameWhatEachPackageShips(): void
    {
        $composer = $this->runMake(['composer-list'], directory: self::PACK_DIRECTORY);
        $bun      = $this->runMake(['bun-list'], directory: self::PACK_DIRECTORY);

        self::assertStringContainsString('src/Example.php', $composer);
        self::assertStringNotContainsString('tests/Example.php', $composer);
        self::assertStringContainsString('src/index.mjs', $bun);
        self::assertStringNotContainsString('tests/', $bun);
    }

    #[Group('build')]
    public function testTheFilesEachPackageShips(): void
    {
        $composer = $this->runMake(['composer-list'], directory: self::PROJECT_DIRECTORY);
        $bun      = $this->runMake(['bun-list'], directory: self::PROJECT_DIRECTORY);

        $this->assertMatchesSnapshot(sprintf(
            "=== composer ===\n%s\n\n=== bun ===\n%s\n",
            Str::trim($composer),
            Str::trim($bun),
        ));
    }
}
