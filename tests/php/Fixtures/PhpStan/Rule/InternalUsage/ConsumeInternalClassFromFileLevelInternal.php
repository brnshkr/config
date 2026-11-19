<?php

/**
 * @internal Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal
 */

declare(strict_types=1);

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass;

function consumeInternalClassFromFileLevelInternal(): void
{
    $object = new InternalClass();
}
