<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalConsumer\Support;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass;

trait ConsumeOwnClassFromTrait
{
    public function reset(): int
    {
        static::boot();

        $this->counter = self::LIMIT;
        $copy          = new self();

        new InternalClass(); // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass` is internal and must not be used from `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalConsumer\Support`.

        return $this->count() + $copy->count() + $this->prepare();
    }
}
