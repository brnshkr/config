<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal;

/**
 * @internal
 */
final class InternalClass
{
    public const string SOME_CONSTANT = 'foo';

    public string $value = 'bar';

    public function doSomething(): void {}

    public static function staticMethod(): void {}
}
