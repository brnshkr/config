<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalConsumer;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass;

function consumeInternalClassFromSiblingNamespace(): void
{
    $object = new InternalClass(); // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass` is internal and must not be used from `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalConsumer`.

    $object->doSomething(); // ERROR Method `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass::doSomething()` is internal and must not be used from `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalConsumer`.
}
