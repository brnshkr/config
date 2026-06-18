<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\BoolishPrefix;

use ArrayAccess;

/**
 * @internal
 *
 * @implements ArrayAccess<int, mixed>
 */
final class BoolishPrefixExternalImplFixture implements ArrayAccess
{
    public function offsetExists(mixed $offset): bool
    {
        return false;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void {}

    public function offsetUnset(mixed $offset): void {}
}
