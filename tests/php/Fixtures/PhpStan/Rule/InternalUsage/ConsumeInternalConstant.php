<?php

declare(strict_types=1);

namespace External\Consumer;

use const Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\DEFINED_INTERNAL_CONSTANT;
use const Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\INTERNAL_CONSTANT;
use const Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\PUBLIC_CONSTANT;

function consumeInternalConstant(): int
{
    return INTERNAL_CONSTANT; // ERROR Constant `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\INTERNAL_CONSTANT` is internal and must not be used from `External\Consumer`.
}

function consumeDefinedInternalConstant(): int
{
    return DEFINED_INTERNAL_CONSTANT; // ERROR Constant `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\DEFINED_INTERNAL_CONSTANT` is internal and must not be used from `External\Consumer`.
}

function consumePublicConstant(): int
{
    return PUBLIC_CONSTANT;
}
