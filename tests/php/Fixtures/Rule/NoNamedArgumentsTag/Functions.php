<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\NoNamedArgumentsTag;

/**
 * @no-named-arguments
 */
function functionWithNoNamedArgumentsTag(string $argument): void {}

/**
 * @internal
 */
function functionWithInternalTag(string $argument): void {}

function functionWithoutTag(string $argument): void {}

function functionWithoutTagNoParameters(): void {}
