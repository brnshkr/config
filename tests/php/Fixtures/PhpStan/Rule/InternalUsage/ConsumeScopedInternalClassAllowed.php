<?php

declare(strict_types=1);

namespace External\AllowedConsumer;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\ScopedInternalClass;

function consumeScopedInternalClassAllowed(): void
{
    $object = new ScopedInternalClass();
}
