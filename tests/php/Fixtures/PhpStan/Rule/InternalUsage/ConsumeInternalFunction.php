<?php

declare(strict_types=1);

namespace External\Consumer;

use function Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\internalFunction;

function consumeInternalFunction(): void
{
    internalFunction(); // ERROR Function `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\internalFunction` is internal and must not be used from `External\Consumer`.
}
