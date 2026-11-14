<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpCsFixer\Fixer;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Json;
use Brnshkr\Config\PhpCsFixer\Fixer\ProperNameFixer;
use Brnshkr\Config\ProperNames;
use Brnshkr\Config\Str;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @internal
 */
#[CoversClass(ProperNameFixer::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(Json::class)]
#[UsesClass(ProperNames::class)]
#[UsesClass(Str::class)]
final class ProperNameFixerTest extends TestCase
{
    public function testCommentsSpellEachNameTheWayItsProjectSpellsIt(): void
    {
        $properNameFixer = new ProperNameFixer();
        $organization    = Str::upperFirst(ComposerJson::forThisLibrary()->getPackageOrganization());
        $tokens          = Tokens::fromCode("<?php\n\n/**\n * Runs Phpstan.\n */\n\$tool = 'Phpstan';\n// On Github.\n");

        $properNameFixer->fix(new SplFileInfo('User.php', '', 'User.php'), $tokens);

        self::assertSame($organization . '/proper_name', $properNameFixer->getName());
        self::assertTrue($properNameFixer->isCandidate($tokens));
        self::assertSame("<?php\n\n/**\n * Runs PHPStan.\n */\n\$tool = 'Phpstan';\n// On GitHub.\n", $tokens->generateCode());
    }
}
