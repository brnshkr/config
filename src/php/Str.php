<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use function array_last;
use function array_slice;
use function count;
use function implode;
use function mb_strtolower;
use function sprintf;
use function str_starts_with;

/**
 * @internal
 */
final readonly class Str
{
    private function __construct() {}

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
     * @phpstan-param list<string> $strings
     * @phpstan-param 'conjunction'|'disjunction' $type
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
