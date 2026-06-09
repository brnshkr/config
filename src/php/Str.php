<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use Closure;

use function array_any;
use function array_filter;
use function array_last;
use function array_slice;
use function array_values;
use function count;
use function implode;
use function is_string;
use function mb_ltrim;
use function mb_rtrim;
use function mb_strlen;
use function mb_strrpos;
use function mb_strtolower;
use function mb_substr;
use function mb_trim;
use function preg_match;
use function preg_quote;
use function preg_replace_callback;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_repeat;
use function str_replace;
use function str_starts_with;

use const PREG_UNMATCHED_AS_NULL;

/**
 * @internal
 */
final readonly class Str
{
    private function __construct() {}

    /**
     * @phpstan-assert-if-false non-empty-string $string
     */
    public static function isEmpty(string $string): bool
    {
        return $string === '';
    }

    public static function length(string $string): int
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
        return mb_strlen($string);
    }

    public static function toLowerCase(string $string): string
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
        return mb_strtolower($string);
    }

    public static function doesStartWith(string $haystack, string $needle): bool
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
        return str_starts_with($haystack, $needle);
    }

    /**
     * @param list<string> $needles
     */
    public static function doesStartWithAny(string $haystack, array $needles): bool
    {
        return array_any(
            $needles,
            static fn (string $needle): bool => self::doesStartWith($haystack, $needle),
        );
    }

    public static function doesEndWith(string $haystack, string $needle): bool
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
        return str_ends_with($haystack, $needle);
    }

    /**
     * @param int<0, max> $times
     */
    public static function repeat(string $string, int $times): string
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
        return str_repeat($string, $times);
    }

    /**
     * @param Closure(array<int|string, string>): string $callback
     */
    public static function replaceMatches(string $subject, string $pattern, Closure $callback): string
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
        return preg_replace_callback($pattern . 'u', $callback, $subject) ?? $subject;
    }

    public static function quoteRegex(string $value, ?string $delimiter = '/'): string
    {
        return preg_quote($value, $delimiter);
    }

    /**
     * @return list<string>
     */
    public static function match(string $string, string $pattern): array
    {
        $matches = [];

        // @phpstan-ignore symplify.forbiddenFuncCall(Avoid using symfony/string here to keep package as lightweight as possible)
        $result = preg_match($pattern . 'u', $string, $matches, PREG_UNMATCHED_AS_NULL);

        return $result === false
            ? []
            : array_values(array_filter($matches, is_string(...)));
    }

    public static function doesContain(string $haystack, string $needle): bool
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
        return str_contains($haystack, $needle);
    }

    public static function replace(string $haystack, string $needle, string $replacement): string
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
        return str_replace($needle, $replacement, $haystack);
    }

    public static function afterLast(string $haystack, string $needle): string
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (See ->), symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
        return mb_substr($haystack, (mb_strrpos($haystack, $needle) ?: -1) + 1);
    }

    public static function getClassShortName(string|object $classOrObject): string
    {
        $fullyQualifiedName = is_string($classOrObject) ? $classOrObject : $classOrObject::class;
        $shortName          = self::afterLast($fullyQualifiedName, '\\');

        return self::isEmpty($shortName) ? $fullyQualifiedName : $shortName;
    }

    /**
     * @param non-empty-string $cwd
     * @param non-empty-string $path
     *
     * @return non-empty-string
     */
    public static function toAbsolutePath(string $cwd, string $path): string
    {
        if (self::doesStartWith($path, '/')) {
            return $path;
        }

        return $cwd . (self::doesStartWith($path, './')
            ? self::trim($path, './', 'start')
            : $path);
    }

    /**
     * @param 'default'|'end'|'start' $mode
     */
    public static function trim(
        string $string,
        string $characters = " \t\n\r\0\x0B\x0C\u{A0}\u{FEFF}",
        string $mode = 'default',
    ): string {
        return match ($mode) {
            // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
            'start' => mb_ltrim($string, $characters),
            // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
            'end' => mb_rtrim($string, $characters),
            // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
            'default' => mb_trim($string, $characters),
        };
    }

    /**
     * @param list<string> $strings
     * @param 'conjunction'|'disjunction' $type
     */
    public static function joinAsQuotedList(
        array $strings,
        string $type = 'conjunction',
    ): string {
        return count($strings) > 1
            ? sprintf(
                '"%s" %s "%s"',
                implode('", "', array_slice($strings, 0, -1)),
                $type === 'conjunction' ? 'and' : 'or',
                array_last($strings),
            )
            : [
                0 => '',
                1 => isset($strings[0]) ? sprintf('"%s"', $strings[0]) : '',
            ][count($strings)];
    }
}
