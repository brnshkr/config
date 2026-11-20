<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Mate;

use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Mate\Tool\ModuleTool;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class ModuleToolTest extends TestCase
{
    public function testAPartTooLongToPrintIsReplacedByTheCallThatPrintsIt(): void
    {
        $config = new ModuleTool()->printModuleConfig('phpstan');

        self::assertLessThanOrEqual(Project::MAX_OUTPUT_LENGTH, Str::length($config));
        self::assertMatchesRegularExpression('/^services: Omitted \d+ characters\. Run `brnshkr-module-config --module=phpstan --section=services`\.$/m', $config);
    }

    public function testASectionPathPrintsTheValueItReaches(): void
    {
        self::assertSame('parameters.level: max', new ModuleTool()->printModuleConfig('phpstan', 'parameters.level'));
    }

    public function testAnUnknownSectionListsTheSectionsBesideIt(): void
    {
        self::assertStringContainsString(
            'Unknown section \"parameters.levle\". Allowed sections are: \"parameters.level\"',
            new ModuleTool()->printModuleConfig('phpstan', 'parameters.levle'),
        );
    }
}
