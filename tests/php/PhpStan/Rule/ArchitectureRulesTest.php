<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\CommandTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\DependencyInjectionTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\MessageTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\TwigComponentTest;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use Brnshkr\Config\Str;
use Override;
use PHPat\Rule\Assertion\Declaration\Exists\ExistsRule;
use PHPat\Rule\Assertion\Declaration\IsFinal\IsFinalRule;
use PHPat\Rule\Assertion\Declaration\IsReadonly\IsReadonlyRule;
use PHPat\Rule\Assertion\Declaration\Named\ClassnameRule;
use PHPat\Rule\Assertion\Relation\Extend\ParentClassRule;
use PHPat\Rule\Assertion\Relation\Implement\ImplementedInterfacesRule;
use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

use function array_map;

/**
 * @internal
 *
 * @extends RuleTestCase<Rule<Node>>
 */
#[CoversClass(CommandTest::class)]
#[CoversClass(DependencyInjectionTest::class)]
#[CoversClass(MessageTest::class)]
#[CoversClass(TwigComponentTest::class)]
#[CoversTrait(ArchitectureRuleTrait::class)]
#[UsesClass(Str::class)]
final class ArchitectureRulesTest extends RuleTestCase
{
    private const string FIXTURE_DIRECTORY = __DIR__ . '/../../Fixtures/PhpStan/Rule/Architecture';
    private const string ROOT              = 'Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Architecture';

    /**
     * @var class-string<Rule<Node>>
     */
    private string $ruleClass = IsFinalRule::class;

    /**
     * @param class-string<Rule<Node>> $ruleClass
     * @param list<string> $fixtureFiles
     * @param list<array{
     *     0: string,
     *     1: int,
     *     2: string,
     * }> $expectedErrors
     */
    #[DataProvider('provideRuleReportsOnlyConcreteClassesCases')]
    public function testRuleReportsOnlyConcreteClasses(string $ruleClass, array $fixtureFiles, array $expectedErrors): void
    {
        $this->ruleClass = $ruleClass;

        $this->analyse(
            array_map(static fn (string $fixtureFile): string => self::FIXTURE_DIRECTORY . '/' . $fixtureFile, $fixtureFiles),
            $expectedErrors,
        );
    }

    /**
     * @return iterable<string, array{
     *     0: class-string<Rule<Node>>,
     *     1: list<string>,
     *     2: list<array{
     *         0: string,
     *         1: int,
     *         2: string,
     *     }>,
     * }>
     */
    public static function provideRuleReportsOnlyConcreteClassesCases(): iterable
    {
        yield 'final skips abstract classes, interfaces and enums' => [
            IsFinalRule::class,
            ['DependencyInjection/Extensions.php', 'Message/Messages.php'],
            [
                [
                    self::ROOT . '\DependencyInjection\UserExtension should be final',
                    23,
                    'DI extension classes must be final.',
                ],
                [
                    self::ROOT . '\Message\WelcomeUserMessage should be final',
                    44,
                    'Messenger messages must be final; they are sealed value objects.',
                ],
            ],
        ];

        yield 'readonly skips abstract classes, interfaces and enums' => [
            IsReadonlyRule::class,
            ['Message/Messages.php'],
            [
                [
                    self::ROOT . '\Message\WelcomeUserMessage should be readonly',
                    44,
                    'Messenger messages must be readonly; they are immutable data bags passed on the bus.',
                ],
            ],
        ];

        yield 'implement skips interfaces' => [
            ImplementedInterfacesRule::class,
            ['DependencyInjection/Compiler/Passes.php'],
            [
                [
                    self::ROOT . '\DependencyInjection\Compiler\RegisterUserPass should implement ' . CompilerPassInterface::class,
                    19,
                    'Compiler passes must implement ' . CompilerPassInterface::class . '.',
                ],
            ],
        ];

        yield 'extend skips interfaces' => [
            ParentClassRule::class,
            ['Command/Commands.php', 'DependencyInjection/Extensions.php'],
            [
                [
                    self::ROOT . '\Command\SyncUserCommand should extend ' . Command::class,
                    19,
                    'Console commands must extend ' . Command::class . '.',
                ],
            ],
        ];

        yield 'components live under Twig\Components' => [
            ClassnameRule::class,
            ['Twig/Components/Components.php', 'Ui/Components.php'],
            [
                [
                    self::ROOT . '\Ui\EmailBadge should be named matching the regex /^'
                        . Str::quoteRegex(self::ROOT . '\Twig\Components') . '\\\.+$/',
                    14,
                    'Twig components must reside in ' . self::ROOT . '\Twig\Components\*.',
                ],
            ],
        ];

        yield 'Twig\Components holds only components' => [
            ExistsRule::class,
            ['Twig/Components/Components.php'],
            [
                [
                    self::ROOT . '\Twig\Components\UserFormatter should not exist',
                    38,
                    'Classes under ' . self::ROOT . '\Twig\Components must carry #[AsTwigComponent] or #[AsLiveComponent].',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    #[Override]
    public static function getAdditionalConfigFiles(): array
    {
        return [self::FIXTURE_DIRECTORY . '/phpat.neon'];
    }

    #[Override]
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType($this->ruleClass);
    }
}
