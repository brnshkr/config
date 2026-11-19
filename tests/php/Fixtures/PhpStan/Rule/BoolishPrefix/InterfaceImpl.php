<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\BoolishPrefix;

/**
 * @internal
 */
interface BoolishPrefixLockSourceInterface
{
    public function compute(): bool; // ERROR missing|Method|compute
}

/**
 * @internal
 */
final class BoolishPrefixInterfaceImplFixture implements BoolishPrefixLockSourceInterface
{
    public function compute(): bool // ERROR missing|Method|compute
    {
        return true;
    }
}
