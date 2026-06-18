<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NoNamedArgumentsTag;

/**
 * @no-named-arguments
 */
interface InterfaceWithNoNamedArgumentsTag
{
    public function someMethod(string $argument): void;
}

/**
 * @internal
 */
interface InterfaceWithInternalTag
{
    public function someMethod(string $argument): void;
}

interface InterfaceWithoutTag // ERROR Interface|InterfaceWithoutTag
{
    public function someMethod(string $argument): void;
}

interface InterfaceWithoutTagNoParameters
{
    public function someMethod(): void;
}
