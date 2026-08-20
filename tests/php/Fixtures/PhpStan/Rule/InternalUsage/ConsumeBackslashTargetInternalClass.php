<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\BackslashConsumer;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\BackslashTargetInternalClass;

function consumeBackslashTargetInternalClass(): void
{
    $object = new BackslashTargetInternalClass();
}
