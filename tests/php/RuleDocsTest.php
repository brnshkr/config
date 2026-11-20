<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\PhpStan\Rule\DevFunctionUsageRule;
use Brnshkr\Config\PhpStan\Rule\FencedCodeRule;
use Brnshkr\Config\PhpStan\Rule\InternalExposureRule;
use Brnshkr\Config\PhpStan\Rule\NamedArgumentsTagRule;
use Brnshkr\Config\PhpStan\Rule\NamedArgumentsUsageRule;
use Brnshkr\Config\PhpStan\Rule\ServiceArgumentBindingRule;
use Brnshkr\Config\PhpStan\Rule\SuperglobalUsageRule;
use Brnshkr\Config\PhpStan\Rule\TagDescriptionRule;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function array_diff;
use function array_map;
use function array_values;
use function sort;
use function Symfony\Component\String\s;

/**
 * @internal
 */
#[CoversNothing]
final class RuleDocsTest extends TestCase
{
    private const array PHP_ONLY_RULES = [
        DevFunctionUsageRule::class,
        FencedCodeRule::class,
        InternalExposureRule::class,
        NamedArgumentsTagRule::class,
        NamedArgumentsUsageRule::class,
        ServiceArgumentBindingRule::class,
        SuperglobalUsageRule::class,
        TagDescriptionRule::class,
    ];

    private const array JS_ONLY_RULES = [
        'require-import-alias',
        'require-import-attributes',
        'require-rejects',
        'require-throws',
        'type-assertion-style',
    ];

    public function testEveryPhpStanRuleHasItsPage(): void
    {
        self::assertSame(
            $this->getFileNames('src/php/PhpStan/Rule', '*Rule.php'),
            $this->getFileNames('docs/php/phpstan/rules', '*Rule.md'),
        );
    }

    public function testEveryEslintRuleHasItsPage(): void
    {
        self::assertSame(
            $this->getFileNames('src/js/eslint/configs/builtin', '*.ts'),
            $this->getFileNames('docs/js/eslint/rules', '*.md'),
        );
    }

    public function testEveryRuleHasItsCounterpartOnTheOtherStack(): void
    {
        $phpOnlyRules = array_map(Str::getClassShortName(...), self::PHP_ONLY_RULES);

        $phpRulesAsJsRules = array_map(
            static fn (string $phpRule): string => s($phpRule)->trimSuffix('Rule')->kebab()->toString(),
            array_diff($this->getFileNames('src/php/PhpStan/Rule', '*Rule.php'), $phpOnlyRules)
                |> array_values(...),
        );

        sort($phpRulesAsJsRules);

        $jsRules = array_diff($this->getFileNames('src/js/eslint/configs/builtin', '*.ts'), self::JS_ONLY_RULES)
            |> array_values(...);

        self::assertSame($jsRules, $phpRulesAsJsRules);
    }

    /**
     * @return list<string>
     */
    private function getFileNames(string $directory, string $namePattern): array
    {
        $finder = new Finder()
            ->files()
            ->in(Path::join(__DIR__, '../..', $directory))
            ->depth(0)
            ->name($namePattern)
            ->notName([
                'index.md',
                'index.ts',
            ])
        ;

        $fileNames = array_map(
            static fn (SplFileInfo $file): string => $file->getFilenameWithoutExtension(),
            [...$finder],
        );

        sort($fileNames);

        return $fileNames;
    }
}
