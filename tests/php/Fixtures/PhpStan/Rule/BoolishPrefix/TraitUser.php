<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\BoolishPrefix;

/**
 * @internal
 */
trait BoolishPrefixLockSourceTrait
{
    public function evaluate(): bool
    {
        return false;
    }
}

/**
 * @internal
 */
final class BoolishPrefixTraitUserFixture
{
    use BoolishPrefixLockSourceTrait;

    public function evaluate(): bool // ERROR missing|Method|evaluate
    {
        return true;
    }
}
