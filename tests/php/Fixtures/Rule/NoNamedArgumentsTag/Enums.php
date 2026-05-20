<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\NoNamedArgumentsTag;

/**
 * @no-named-arguments
 */
enum EnumWithNoNamedArgumentsTag
{
    case A;

    public function someMethod(string $argument): void {}
}

/**
 * @internal
 */
enum EnumWithInternalTag
{
    case A;

    public function someMethod(string $argument): void {}
}

enum EnumWithoutTag
{
    case A;

    public function someMethod(string $argument): void {}
}

enum EnumWithoutTagNoParameters
{
    case A;
}
