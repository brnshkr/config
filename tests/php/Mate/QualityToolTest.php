<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Mate;

use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Mate\Tool\QualityTool;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[CoversNothing]
final class QualityToolTest extends TestCase
{
    private const string PAGE_PATH = 'docs/quality-tool-test.md';

    #[After]
    public function removeThePage(): void
    {
        new Filesystem()->remove(Project::getRootDirectory() . '/' . self::PAGE_PATH);
    }

    public function testAFixFlagIsRefusedInADryRun(): void
    {
        self::assertStringContainsString(
            'The \"--fix\" option cannot be used in a dry run.',
            new QualityTool()->runQualityTool('eslint', arguments: '\'--fix\' ./src/user.ts'),
        );
    }

    public function testADryRunAnswersOneRowPerFinding(): void
    {
        new Filesystem()->dumpFile(Project::getRootDirectory() . '/' . self::PAGE_PATH, "#Acme\n");

        self::assertSame(
            "exitCode: 1\nfindings[2]{file,line,rule,message}:\n"
            . "  docs/quality-tool-test.md,1,no-missing-space-atx,\"No space after hash on atx style heading [Context: \\\"#Acme\\\"]\"\n"
            . '  docs/quality-tool-test.md,1,first-line-heading,"First line in a file should be a top-level heading [Context: \"#Acme\"]"',
            new QualityTool()->runQualityTool('markdownlint', arguments: './' . self::PAGE_PATH),
        );
    }
}
