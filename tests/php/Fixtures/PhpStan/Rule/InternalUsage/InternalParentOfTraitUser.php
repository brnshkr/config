<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalConsumer\User;

/**
 * @internal
 */
abstract class InternalParentOfTraitUser
{
    public function prepare(): int
    {
        return 1;
    }
}
