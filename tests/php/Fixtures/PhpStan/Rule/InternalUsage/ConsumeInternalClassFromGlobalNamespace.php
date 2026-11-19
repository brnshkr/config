<?php

declare(strict_types=1);

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass;

function consumeInternalClassFromGlobalNamespace(): void
{
    $object = new InternalClass(); // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass` is internal and must not be used from the global namespace.
    $object->doSomething(); // ERROR Method `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass::doSomething()` is internal and must not be used from the global namespace.

    $value = $object->value; // ERROR Property `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass::$value` is internal and must not be used from the global namespace.

    $constant = InternalClass::SOME_CONSTANT; // ERROR Constant `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass::SOME_CONSTANT` is internal and must not be used from the global namespace.

    InternalClass::staticMethod(); // ERROR Method `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass::staticMethod()` is internal and must not be used from the global namespace.
}
