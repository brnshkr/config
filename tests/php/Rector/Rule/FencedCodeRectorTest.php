<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Rector\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\FencedCode;
use Brnshkr\Config\FileFinder;
use Brnshkr\Config\Json;
use Brnshkr\Config\Rector\Rule\FencedCodeRector;
use Brnshkr\Config\Str;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

/**
 * @internal
 */
#[CoversClass(FencedCodeRector::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(FencedCode::class)]
#[UsesClass(FileFinder::class)]
#[UsesClass(Json::class)]
#[UsesClass(Str::class)]
final class FencedCodeRectorTest extends AbstractRectorTestCase
{
    private const string FIXTURE_DIRECTORY = __DIR__ . '/../../Fixtures/Rector/Rule/FencedCode';

    #[DataProvider('provideTheCodeBlocksOfAFixtureAreRefactoredCases')]
    public function testTheCodeBlocksOfAFixtureAreRefactored(string $fixturePath): void
    {
        $this->doTestFile($fixturePath);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideTheCodeBlocksOfAFixtureAreRefactoredCases(): iterable
    {
        return self::yieldFilesFromDirectory(self::FIXTURE_DIRECTORY, '*.inc');
    }

    #[Override]
    public function provideConfigFilePath(): string
    {
        return self::FIXTURE_DIRECTORY . '/Config.php';
    }
}
