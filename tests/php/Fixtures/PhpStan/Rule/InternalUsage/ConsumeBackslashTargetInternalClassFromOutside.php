<?php

declare(strict_types=1);

namespace External\Consumer;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\BackslashTargetInternalClass;

function consumeBackslashTargetInternalClassFromOutside(): void
{
    $object = new BackslashTargetInternalClass(); // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\BackslashTargetInternalClass` is internal to `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\BackslashConsumer` and must not be used from `External\Consumer`.
}
