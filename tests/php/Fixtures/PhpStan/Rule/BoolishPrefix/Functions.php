<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\BoolishPrefix;

/**
 * @internal
 */
function isEnabled(): bool
{
    return true;
}

/**
 * @internal
 */
function checkEnabled(): bool // ERROR missing|Function|checkEnabled
{
    return true;
}
