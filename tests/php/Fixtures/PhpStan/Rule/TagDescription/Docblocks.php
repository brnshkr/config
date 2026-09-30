<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\TagDescription;

use RuntimeException;

/**
 * @template TValue of object - the held value
 * @template TOther the other value // ERROR The `@template` description needs a leading dash.
 *
 * @property-read string $name - the name
 * @property string $label The label. // ERROR The `@property` description needs a leading dash.
 */
final class Holder
{
    /**
     * @param string $dashed - the dashed one
     * @param string $undashed the dash is missing // ERROR The `@param` description needs a leading dash.
     * @param string $sentence - The first word is capitalized // ERROR The `@param` description starts lowercase and ends without a period.
     * @param string $period - ends with a period. // ERROR The `@param` description starts lowercase and ends without a period.
     * @param string $acronym - URL of the thing
     * @param string $code - `Foo` instance
     * @param array{ // ERROR The `@param` description starts lowercase and ends without a period.
     *     key: int,
     * } $shape - The shape spans lines
     * @param string $bare
     * @phpstan-param non-empty-string $dashed the phpstan twin // ERROR The `@phpstan-param` description needs a leading dash.
     *
     * @return string - the dashed return // ERROR The `@return` description takes no leading dash.
     *
     * @throws RuntimeException when it fails
     * @throws RuntimeException unless it is set
     * @throws RuntimeException if it fails // ERROR The `@throws` description starts with `when` or `unless`.
     * @throws RuntimeException
     */
    public function run(
        string $dashed,
        string $undashed,
        string $sentence,
        string $period,
        string $acronym,
        string $code,
        array $shape,
        string $bare,
    ): string {
        return $dashed . $undashed . $sentence . $period . $acronym . $code . $bare . $shape['key'];
    }

    /**
     * @return array{
     *     key: int,
     * } the value spanning lines
     */
    public function read(): array
    {
        return ['key' => 1];
    }
}
