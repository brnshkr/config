<?php

declare(strict_types=1);

namespace External\NarrowedConsumer;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass;

function consumeInternalClassOutsideAllowedTargets(): void
{
    $object = new InternalClass(); // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass` is internal and must not be used from `External\NarrowedConsumer`.
}
