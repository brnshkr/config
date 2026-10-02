<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_key_first;
use function is_string;
use function putenv;
use function sprintf;

use const PHP_INT_MAX;

/**
 * @internal
 */
#[CoversClass(Str::class)]
final class StrTest extends TestCase
{
    private const string VARIABLE_NAME = 'ACME_STR_PROBE';

    #[After]
    public function removeTheProbedVariable(): void
    {
        unset($_SERVER[self::VARIABLE_NAME], $_ENV[self::VARIABLE_NAME]);

        putenv(self::VARIABLE_NAME);
    }

    #[DataProvider('provideAStringIsANonDecimalIntStringExactlyWhenPhpKeepsItAsAnArrayKeyCases')]
    public function testAStringIsANonDecimalIntStringExactlyWhenPhpKeepsItAsAnArrayKey(string $candidate): void
    {
        $key = array_key_first([$candidate => true]);

        self::assertSame(is_string($key), Str::isNonDecimalIntString($candidate));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAStringIsANonDecimalIntStringExactlyWhenPhpKeepsItAsAnArrayKeyCases(): iterable
    {
        foreach (['/app/composer.json', 'App\\', '', '-0', '01', ' 1', '1.5', PHP_INT_MAX . '0', '0', '1', '-1', (string) PHP_INT_MAX] as $candidate) {
            yield sprintf('"%s"', $candidate) => [$candidate];
        }
    }

    #[DataProvider('provideOnlyTheEmptyStringIsEmptyCases')]
    public function testOnlyTheEmptyStringIsEmpty(bool $isEmpty, string $candidate): void
    {
        self::assertSame($isEmpty, Str::isEmpty($candidate));
    }

    /**
     * @return iterable<string, array{bool, string}>
     */
    public static function provideOnlyTheEmptyStringIsEmptyCases(): iterable
    {
        yield 'empty' => [true, ''];

        yield 'a space' => [false, ' '];

        yield 'zero' => [false, '0'];
    }

    public function testLengthAndCaseWorkOnCharactersRatherThanBytes(): void
    {
        self::assertSame(3, Str::length('äöü'));
        self::assertSame('äöü', Str::toLowerCase('ÄÖÜ'));
        self::assertSame('ä', Str::slice('äbc', 0, 1));
        self::assertSame('bc', Str::slice('äbc', 1));
    }

    public function testAffixChecksCompareTheWholeNeedle(): void
    {
        self::assertTrue(Str::startsWith('Acme\User', 'Acme\\'));
        self::assertFalse(Str::startsWith('Acme\User', 'User'));
        self::assertTrue(Str::endsWith('composer.json', '.json'));
        self::assertFalse(Str::endsWith('composer.json', 'composer'));
        self::assertTrue(Str::contains('composer.json', 'poser'));
        self::assertFalse(Str::contains('composer.json', 'Poser'));
        self::assertTrue(Str::startsWithAny('./src', ['/', './']));
        self::assertFalse(Str::startsWithAny('src', ['/', './']));
        self::assertFalse(Str::startsWithAny('src', []));
    }

    public function testRepeatAndReplaceBuildTheExpectedString(): void
    {
        self::assertSame('ababab', Str::repeat('ab', 3));
        self::assertSame('', Str::repeat('ab', 0));
        self::assertSame('Acme/User/Email', Str::replace('Acme\User\Email', '\\', '/'));
    }

    public function testAPatternIsAppliedAsUnicode(): void
    {
        self::assertSame('--', Str::replaceMatches('äb', '/./', static fn (): string => '-'));
        self::assertSame(['ä'], Str::match('äb', '/^./'));
    }

    public function testAMatchKeepsOnlyTheGroupsThatTookPart(): void
    {
        $matched = Str::match('2026-10', '/(?<year>\d{4})-(?<month>\d{2})(?:-(?<day>\d{2}))?/');

        self::assertSame('2026', $matched['year'] ?? null);
        self::assertSame('10', $matched['month'] ?? null);
        self::assertArrayNotHasKey('day', $matched);
        self::assertSame([], Str::match('none', '/\d/'));
    }

    public function testEveryMatchIsOneSet(): void
    {
        $matches = Str::matchAll('a1 b2', '/(?<letter>[a-z])(?<digit>\d)/');

        self::assertCount(2, $matches);
        self::assertSame('b', $matches[1]['letter'] ?? null);
        self::assertSame('2', $matches[1]['digit'] ?? null);
        self::assertSame([], Str::matchAll('none', '/\d/'));
    }

    public function testAQuotedValueMatchesItselfLiterally(): void
    {
        self::assertSame('a\/b\.c', Str::quoteRegex('a/b.c'));
        self::assertSame('a/b\.c', Str::quoteRegex('a/b.c', null));
        self::assertNotSame([], Str::match('a/b.c', '/^' . Str::quoteRegex('a/b.c') . '$/'));
        self::assertSame([], Str::match('a/bxc', '/^' . Str::quoteRegex('a/b.c') . '$/'));
    }

    #[DataProvider('provideARegexIsDelimitedOnBothEndsCases')]
    public function testARegexIsDelimitedOnBothEnds(bool $isRegex, string $candidate): void
    {
        self::assertSame($isRegex, Str::isRegex($candidate));
    }

    /**
     * @return iterable<string, array{bool, string}>
     */
    public static function provideARegexIsDelimitedOnBothEndsCases(): iterable
    {
        yield 'slashes' => [true, '/Acme/'];

        yield 'hashes with flags' => [true, '#acme#iu'];

        yield 'a multi-line body' => [true, "~a\nb~"];

        yield 'a plain word' => [false, 'Acme'];

        yield 'an unclosed pattern' => [false, '/Acme'];

        yield 'a word character as delimiter' => [false, 'aAcmea'];

        yield 'a backslash as delimiter' => [false, '\Acme\\'];
    }

    public function testTheLastSeparatorSplitsTheString(): void
    {
        self::assertSame('Acme\User', Str::beforeLast('Acme\User\Email', '\\'));
        self::assertSame('Email', Str::afterLast('Acme\User\Email', '\\'));
        self::assertSame('', Str::beforeLast('Email', '\\'));
        self::assertSame('Email', Str::afterLast('Email', '\\'));
        self::assertSame('äb', Str::afterLast('x→äb', '→'));
    }

    public function testASuffixIsTrimmedOnlyWhenItIsThere(): void
    {
        self::assertSame('composer', Str::trimSuffix('composer.json', '.json'));
        self::assertSame('composer.json', Str::trimSuffix('composer.json', '.lock'));
        self::assertSame('composer.json', Str::trimSuffix('composer.json', ''));
        self::assertSame('', Str::trimSuffix('.json', '.json'));
    }

    public function testAShortNameDropsTheNamespace(): void
    {
        self::assertSame('Email', Str::getClassShortName('Acme\User\Email'));
        self::assertSame('stdClass', Str::getClassShortName(new stdClass()));
        self::assertSame('StrTest', Str::getClassShortName(self::class));
    }

    /**
     * @param non-empty-string $path
     */
    #[DataProvider('provideAPathIsResolvedAgainstTheWorkingDirectoryUnlessAbsoluteCases')]
    public function testAPathIsResolvedAgainstTheWorkingDirectoryUnlessAbsolute(string $expected, string $path): void
    {
        self::assertSame($expected, Str::toAbsolutePath('/acme/', $path));
    }

    /**
     * @return iterable<string, array{non-empty-string, non-empty-string}>
     */
    public static function provideAPathIsResolvedAgainstTheWorkingDirectoryUnlessAbsoluteCases(): iterable
    {
        yield 'absolute' => ['/etc/acme', '/etc/acme'];

        yield 'relative' => ['/acme/src', 'src'];

        yield 'dot-prefixed' => ['/acme/src', './src'];

        yield 'dot-prefixed dotfile' => ['/acme/.env', './.env'];

        yield 'dot-prefixed dot directory' => ['/acme/.cache/acme.json', './.cache/acme.json'];

        yield 'parent' => ['/acme/../user', '../user'];
    }

    /**
     * @param 'default'|'end'|'start' $mode
     */
    #[DataProvider('provideTrimRemovesOnlyWhatItsModeNamesCases')]
    public function testTrimRemovesOnlyWhatItsModeNames(
        string $expected,
        string $string,
        string $characters,
        string $mode,
    ): void {
        self::assertSame($expected, Str::trim($string, $characters, $mode));
    }

    /**
     * @return iterable<string, array{string, string, string, 'default'|'end'|'start'}>
     */
    public static function provideTrimRemovesOnlyWhatItsModeNamesCases(): iterable
    {
        yield 'both ends' => ['acme', '--acme--', '-', 'default'];

        yield 'start' => ['acme--', '--acme--', '-', 'start'];

        yield 'end' => ['--acme', '--acme--', '-', 'end'];

        yield 'multibyte characters' => ['acme', '→acme→', '→', 'default'];
    }

    public function testTrimRemovesUnicodeWhitespaceByDefault(): void
    {
        self::assertSame('acme', Str::trim("\u{FEFF}\u{A0} acme\t\n"));
    }

    /**
     * @param list<string> $strings
     * @param 'conjunction'|'disjunction' $type
     */
    #[DataProvider('provideAQuotedListReadsAsProseCases')]
    public function testAQuotedListReadsAsProse(string $expected, array $strings, string $type): void
    {
        self::assertSame($expected, Str::joinAsQuotedList($strings, $type));
    }

    /**
     * @return iterable<string, array{string, list<string>, 'conjunction'|'disjunction'}>
     */
    public static function provideAQuotedListReadsAsProseCases(): iterable
    {
        yield 'none' => ['', [], 'conjunction'];

        yield 'one' => ['"acme"', ['acme'], 'conjunction'];

        yield 'two' => ['"user" and "email"', ['user', 'email'], 'conjunction'];

        yield 'three as alternatives' => ['"user", "email" or "acme"', ['user', 'email', 'acme'], 'disjunction'];
    }

    public function testTheServerArrayWinsOverTheEnvironmentArrayAndTheProcess(): void
    {
        putenv(self::VARIABLE_NAME . '=process');

        self::assertSame('process', Str::fromEnvironment(self::VARIABLE_NAME));

        $_ENV[self::VARIABLE_NAME] = 'environment';

        self::assertSame('environment', Str::fromEnvironment(self::VARIABLE_NAME));

        $_SERVER[self::VARIABLE_NAME] = 'server';

        self::assertSame('server', Str::fromEnvironment(self::VARIABLE_NAME));
    }

    public function testAnUnsetVariableReadsAsEmpty(): void
    {
        self::assertSame('', Str::fromEnvironment(self::VARIABLE_NAME));
    }
}
