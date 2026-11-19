<?php

declare(strict_types=1);

namespace External\Consumer;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\ScopedInternalClass;

function consumeScopedInternalClass(): void
{
    $object = new ScopedInternalClass(); // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\ScopedInternalClass` is internal to `Brnshkr\Config` and must not be used from `External\Consumer`.
}
