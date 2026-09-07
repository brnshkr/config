<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NamedArgumentsTag;

/**
 * @no-named-arguments
 */
function functionWithNoNamedArgumentsTag(string $argument): void {}

/**
 * @internal
 */
function functionWithInternalTag(string $argument): void {}

function functionWithoutTag(string $argument): void {} // ERROR Function|functionWithoutTag

function functionWithoutTagNoParameters(): void {}
