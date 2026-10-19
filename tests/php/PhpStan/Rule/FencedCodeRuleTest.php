<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\FencedCode;
use Brnshkr\Config\Json;
use Brnshkr\Config\PhpStan\Rule\FencedCodeRule;
use Brnshkr\Config\PhpStan\Rule\NamedArgumentsUsageRule;
use Brnshkr\Config\PhpStan\Rule\PhpExampleAnalyzer;
use Brnshkr\Config\Str;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use Override;
use PHPStan\Analyser\Error;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

use function array_map;
use function array_unique;
use function array_values;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<FencedCodeRule>
 */
#[CoversClass(FencedCodeRule::class)]
#[CoversClass(PhpExampleAnalyzer::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(FencedCode::class)]
#[UsesClass(Json::class)]
#[UsesClass(NamedArgumentsUsageRule::class)]
#[UsesClass(Str::class)]
final class FencedCodeRuleTest extends AbstractRuleTestCase
{
    private const string FIXTURE_DIRECTORY = __DIR__ . '/../../Fixtures/PhpStan/Rule/FencedCode';

    public function testRuleChecksTheCodeBlocksOfEveryDocblock(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Library.php',
            self::FIXTURE_DIRECTORY . '/Examples/Examples.php',
        );
    }

    public function testRuleChecksTheCodeBlocksOfAPageAndTheDocblocksInsideThem(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Library.php',
            self::FIXTURE_DIRECTORY . '/Page.md',
            self::FIXTURE_DIRECTORY . '/Page.markdown',
        );
    }

    public function testRuleReportsEachMistakeUnderTheIdentifierPhpStanGivesIt(): void
    {
        $identifiers = array_map(
            static fn (Error $error): ?string => $error->getIdentifier(),
            $this->gatherAnalyserErrors([
                self::FIXTURE_DIRECTORY . '/Library.php',
                self::FIXTURE_DIRECTORY . '/Examples/Examples.php',
            ]),
        );

        self::assertEqualsCanonicalizing([
            'argument.named',
            'argument.unknown',
            'arguments.count',
            'class.notFound',
            'classConstant.notFound',
            'method.notFound',
            'phpstan.parse',
            'staticMethod.notFound',
            NamedArgumentsUsageRule::getIdentifier(),
        ], array_values(array_unique($identifiers)));
    }

    public function testRuleLeavesTheInlineHtmlOfATemplateAlone(): void
    {
        $this->assertIssuesReported(self::FIXTURE_DIRECTORY . '/Template.html.php');
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new FencedCodeRule(
            self::getContainer()->getByType(ReflectionProvider::class),
            self::getContainer()->getByType(FileTypeMapper::class),
        );
    }
}
