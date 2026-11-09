<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalConsumer\User;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalConsumer\Support\ConsumeOwnClassFromTrait;

/**
 * @internal
 */
final class InternalClassUsingTrait extends InternalParentOfTraitUser
{
    use ConsumeOwnClassFromTrait;

    public const int LIMIT = 1;

    public int $counter = 0;

    public static function boot(): void {}

    public function count(): int
    {
        return $this->counter;
    }
}
