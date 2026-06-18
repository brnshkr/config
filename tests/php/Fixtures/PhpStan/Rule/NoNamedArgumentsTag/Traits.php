<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NoNamedArgumentsTag;

/**
 * @no-named-arguments
 */
trait TraitWithNoNamedArgumentsTag
{
    public function someMethod(string $argument): void {}
}

/**
 * @internal
 */
trait TraitWithInternalTag
{
    public function someMethod(string $argument): void {}
}

trait TraitWithoutTag // ERROR Trait|TraitWithoutTag
{
    public function someMethod(string $argument): void {}
}

trait TraitWithoutTagNoParameters
{
    public function someMethod(): void {}
}
