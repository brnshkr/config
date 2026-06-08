<?php

declare(strict_types=1);

use Brnshkr\Config\Tests\Fixtures\Rule\Internal\InternalClass;

function consumeInternalClassFromGlobalNamespace(): void
{
    $object = new InternalClass();
    $object->doSomething();

    $value    = $object->value;

    $constant = InternalClass::SOME_CONSTANT;

    InternalClass::staticMethod();
}
