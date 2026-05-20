<?php

declare(strict_types=1);

namespace External\Consumer;

use Brnshkr\Config\Tests\Fixtures\Rule\Internal\ScopedInternalClass;

function consumeScopedInternalClass(): void
{
    $object = new ScopedInternalClass();
}
