<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\Nested;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass;

function consumeInternalClassFromNestedNamespace(): void
{
    $object = new InternalClass();

    $object->doSomething();

    $value    = $object->value;
    $constant = InternalClass::SOME_CONSTANT;

    InternalClass::staticMethod();
}
