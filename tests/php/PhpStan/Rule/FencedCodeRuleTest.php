<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\FencedCode;
use Brnshkr\Config\FileFinder;
use Brnshkr\Config\Json;
use Brnshkr\Config\PhpStan\Rule\FencedCodeRule;
use Brnshkr\Config\PhpStan\Rule\MakefileExampleAnalyzer;
use Brnshkr\Config\PhpStan\Rule\NamedArgumentsUsageRule;
use Brnshkr\Config\PhpStan\Rule\PhpExampleAnalyzer;
use Brnshkr\Config\PhpStan\Rule\XmlExampleAnalyzer;
use Brnshkr\Config\Process;
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
#[CoversClass(MakefileExampleAnalyzer::class)]
#[CoversClass(PhpExampleAnalyzer::class)]
#[CoversClass(XmlExampleAnalyzer::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(FencedCode::class)]
#[UsesClass(FileFinder::class)]
#[UsesClass(Json::class)]
#[UsesClass(NamedArgumentsUsageRule::class)]
#[UsesClass(Process::class)]
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

    public function testRuleChecksXmlAndMakefileBlocks(): void
    {
        $this->analyse([self::FIXTURE_DIRECTORY . '/Blocks.md'], [
            ['Code block does not parse: Opening and ending tag mismatch: testsuite line 2 and testsuites.', 6],
            ['Code block does not match its schema: Element \'unknown\': This element is not expected.', 11],
            ['Code block does not match its schema: Element \'{https://phar.io/xml/manifest/1.0}unknown\': This element is not expected. Expected is ( {https://phar.io/xml/manifest/1.0}contains ).', 23],
            ['Code block does not parse: missing separator.', 29],
            ['Code block fails `make help`: Unknown symbol `acme-unknown`.', 35],
            ['Code block fails `make help`: Unknown symbol `acme-missing`.', 41],
        ]);
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
            self::getContainer(),
        );
    }
}
