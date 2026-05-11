<?php

declare(strict_types=1);

namespace Brnshkr\Config;

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
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;

use const PREG_UNMATCHED_AS_NULL;

/**
 * @internal
 */
final readonly class Str
{
    private function __construct() {}

    public static function length(string $string): int
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lighweight as possible)
        return mb_strlen($string);
    }

    public static function toLowerCase(string $string): string
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lighweight as possible)
        return mb_strtolower($string);
    }

    public static function doesStartWith(string $haystack, string $needle): bool
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lighweight as possible)
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
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lighweight as possible)
        return str_ends_with($haystack, $needle);
    }

    /**
     * @return list<string>
     */
    public static function match(string $string, string $pattern): array
    {
        $matches = [];

        // @phpstan-ignore symplify.forbiddenFuncCall(Avoid using symfony/string here to keep package as lighweight as possible)
        $result = preg_match($pattern . 'u', $string, $matches, PREG_UNMATCHED_AS_NULL);

        return $result === false
            ? []
            : array_values(array_filter($matches, is_string(...)));
    }

    public static function doesContain(string $haystack, string $needle): bool
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lighweight as possible)
        return str_contains($haystack, $needle);
    }

    public static function replace(string $haystack, string $needle, string $replacement): string
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lighweight as possible)
        return str_replace($needle, $replacement, $haystack);
    }

    public static function afterLast(string $haystack, string $needle): string
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (See ->), symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lighweight as possible)
        return mb_substr($haystack, (mb_strrpos($haystack, $needle) ?: -1) + 1);
    }

    /**
     * @param 'default'|'end'|'start' $mode
     */
    public static function trim(
        string $string,
        string $characters = " \t\n\r\0\x0B\x0C\u{A0}\u{FEFF}",
        string $mode = 'default',
    ): string {
        $result = match ($mode) {
            // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
            'start' => mb_ltrim($string, $characters),
            // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
            'end' => mb_rtrim($string, $characters),
            // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
            'default' => mb_trim($string, $characters),
        };

        if (!is_string($result)) {
            return $string;
        }

        return $result;
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
