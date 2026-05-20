<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\BoolishPrefix;

/**
 * @internal
 */
interface BoolishPrefixLockSourceInterface
{
    public function compute(): bool;
}

/**
 * @internal
 */
final class BoolishPrefixInterfaceImplFixture implements BoolishPrefixLockSourceInterface
{
    public function compute(): bool
    {
        return true;
    }
}
