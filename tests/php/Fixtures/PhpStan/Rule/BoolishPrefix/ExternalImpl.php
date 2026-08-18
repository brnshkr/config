<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\BoolishPrefix;

use ArrayAccess;
use Exception;

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

/**
 * Constructors are never upstream-locked: their parameters and promoted properties are the
 * project's own to name even when the parent (here an external class) declares a constructor.
 *
 * @internal
 */
final class BoolishPrefixExternalParentFixture extends Exception
{
    public function __construct(public bool $fatal) // ERROR missing|Property|fatal
    {
        parent::__construct();
    }
}
