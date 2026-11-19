<?php

declare(strict_types=1);

/**
 * @internal Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal
 */

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass;

function consumeInternalClassFromFileLevelInternalBelowDeclare(): void
{
    $object = new InternalClass();
}
