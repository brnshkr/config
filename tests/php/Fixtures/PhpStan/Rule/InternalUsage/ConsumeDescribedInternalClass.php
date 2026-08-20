<?php

declare(strict_types=1);

namespace External\Consumer;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\DescribedInternalClass;

function consumeDescribedInternalClass(): void
{
    $object = new DescribedInternalClass(); // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\DescribedInternalClass` is internal and must not be used from `External\Consumer`.

    $object->doSomething(); // ERROR Method `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\DescribedInternalClass::doSomething` is internal and must not be used from `External\Consumer`.
}
