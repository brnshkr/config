<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NamedArgumentsTag;

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

enum EnumWithoutTag // ERROR Enum|EnumWithoutTag
{
    case A;

    public function someMethod(string $argument): void {}
}

enum EnumWithoutTagNoParameters
{
    case A;
}
