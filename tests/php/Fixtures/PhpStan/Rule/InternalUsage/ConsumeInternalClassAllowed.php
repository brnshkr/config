<?php

declare(strict_types=1);

namespace External\AllowedConsumer;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass;

function consumeInternalClassAllowed(): void
{
    $object = new InternalClass();

    $constant = InternalClass::SOME_CONSTANT;
}
