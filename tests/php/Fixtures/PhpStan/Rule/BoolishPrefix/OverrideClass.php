<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\BoolishPrefix;

use Override;

/**
 * @internal
 */
class BoolishPrefixLockSourceParent
{
    public function __construct(public bool $value = true) {} // ERROR missing|Property|value

    public function getResult( // ERROR missing|Method|getResult
        bool $forced, // ERROR missing|Parameter|forced
    ): bool {
        return $forced;
    }
}

/**
 * @internal
 */
final class BoolishPrefixOverrideFixture extends BoolishPrefixLockSourceParent
{
    public function __construct(public bool $value = false) // ERROR missing|Property|value
    {
        parent::__construct($value);
    }

    #[Override] // ERROR missing|Method|getResult
    public function getResult(bool $forced): bool // ERROR missing|Parameter|forced
    {
        $result = true; // ERROR missing|Variable|result

        return $result && $forced;
    }
}
