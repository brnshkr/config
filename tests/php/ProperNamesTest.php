<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\ProperNames;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ProperNames::class)]
#[UsesClass(Str::class)]
final class ProperNamesTest extends TestCase
{
    #[DataProvider('provideEachNameIsSpelledTheWayItsProjectSpellsItCases')]
    public function testEachNameIsSpelledTheWayItsProjectSpellsIt(string $comment, string $expectedComment): void
    {
        self::assertSame($expectedComment, ProperNames::correct($comment));
    }

    /**
     * @return iterable<string, array{
     *     0: string,
     *     1: string,
     * }>
     */
    public static function provideEachNameIsSpelledTheWayItsProjectSpellsItCases(): iterable
    {
        yield 'a name in prose' => [
            '// Runs Phpstan on Github.',
            '// Runs PHPStan on GitHub.',
        ];

        yield 'a name of several words or with a dot' => [
            '// Runs on Github actions and Node.JS.',
            '// Runs on GitHub Actions and Node.js.',
        ];

        yield 'the exact and the all-lowercase spelling' => [
            '// PHPStan runs as phpstan, php-cs-fixer as PHP-CS-Fixer.',
            '// PHPStan runs as phpstan, php-cs-fixer as PHP-CS-Fixer.',
        ];

        yield 'a name joined to a path, a package, a namespace or a class reference' => [
            '// vendor/Phpstan, @Phpstan, Config\PhpStan\Rule, PhpStan::getConfig()',
            '// vendor/Phpstan, @Phpstan, Config\PhpStan\Rule, PhpStan::getConfig()',
        ];

        yield 'code spans, URLs and inline tags' => [
            "/**\n * `Phpstan`, https://example.com/Phpstan and {@see PhpStan}.\n */",
            "/**\n * `Phpstan`, https://example.com/Phpstan and {@see PhpStan}.\n */",
        ];

        yield 'a code block' => [
            "/**\n * Built on Github:\n *\n * ```php\n * \$builder = new Phpstan();\n * ```\n */",
            "/**\n * Built on GitHub:\n *\n * ```php\n * \$builder = new Phpstan();\n * ```\n */",
        ];

        yield 'the type part of a docblock tag' => [
            "/**\n * @param PhpStan \$builder - the Phpstan builder\n * @return PhpStan the Phpstan builder\n"
            . " * @phpstan-import-type Rule from PhpStan\n */",
            "/**\n * @param PhpStan \$builder - the PHPStan builder\n * @return PhpStan the PHPStan builder\n"
            . " * @phpstan-import-type Rule from PhpStan\n */",
        ];
    }
}
