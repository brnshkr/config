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

const IS_FEATURE_ENABLED = true;
const FEATURE_ENABLED    = true; // ERROR missing|Constant|FEATURE_ENABLED
const IS_FEATURE_LABEL   = 'x'; // ERROR reserved|Constant|IS_FEATURE_LABEL|is
