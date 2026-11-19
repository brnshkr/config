<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\BoolishPrefix;

/**
 * @internal
 */
final class BoolishPrefixMagicFixture
{
    public function __isset(string $name): bool
    {
        return false;
    }

    public function __construct(bool $magic) {} // ERROR missing|Parameter|magic
}
