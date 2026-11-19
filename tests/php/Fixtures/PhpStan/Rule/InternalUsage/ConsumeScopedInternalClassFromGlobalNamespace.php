<?php

declare(strict_types=1);

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\ScopedInternalClass;

function consumeScopedInternalClassFromGlobalNamespace(): void
{
    $object = new ScopedInternalClass(); // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\ScopedInternalClass` is internal to `Brnshkr\Config` and must not be used from the global namespace.
}
