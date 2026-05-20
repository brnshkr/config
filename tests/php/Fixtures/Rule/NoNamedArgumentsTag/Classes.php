<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\NoNamedArgumentsTag;

/**
 * @no-named-arguments
 */
final class ClassWithNoNamedArgumentsTag
{
    public function someMethod(string $argument): void {}
}

/**
 * @internal
 */
final class ClassWithInternalTag
{
    public function someMethod(string $argument): void {}
}

final class ClassWithoutTag
{
    public function methodWithoutTag(string $argument): void {}

    /**
     * @no-named-arguments
     */
    public function methodWithNoNamedArgumentsTag(): void {}

    /**
     * @internal
     */
    public function methodWithInternalTag(): void {}
}

final class ClassWithoutTagNoParameters
{
    public function methodWithoutParameters(): void {}
}

final class ClassWithoutTagOnlyPrivateParameters
{
    private function privateMethod(string $argument): void {}
}

final class ClassWithoutTagPrivateAndPublicParameters
{
    private function privateMethod(string $argument): void {}

    public function publicMethod(string $argument): void {}
}

final class ClassWithoutTagConstructorParameters
{
    public function __construct(private readonly string $name) {}
}
