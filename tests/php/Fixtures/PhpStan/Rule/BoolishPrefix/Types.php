<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\BoolishPrefix;

/**
 * @internal
 */
final class BoolishPrefixTypesFixture
{
    // Untyped constants classified by their literal value.
    public const IS_ON  = true;
    public const ON     = true; // ERROR missing|Constant|ON
    public const IS_TAG = 'x'; // ERROR reserved|Constant|IS_TAG|is
    public const TAG    = 'x';

    // Nullable bool resolves to bool.
    public ?bool $isOptional = null;
    public ?bool $optional   = null; // ERROR missing|Property|optional

    // bool|null union resolves to bool.
    public bool|null $isUnion = null;
    public bool|null $union   = null; // ERROR missing|Property|union

    // bool mixed with another type is ambiguous → unknown → unchecked either way.
    public bool|int $isAmbiguous = 0;
    public bool|int $ambiguous   = 0;

    // Bool-less union is non-bool → reverse check applies.
    public int|string $isMixed = 0; // ERROR reserved|Property|isMixed|is
    public int|string $mixed   = 0;

    // Untyped parameter is unknown → skipped.
    public function inspect(bool $isReady, $untyped): void {}
}
