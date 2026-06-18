<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ApiOrInternalTag;

/**
 * @api
 */
function functionWithApiTag(): void {}

/**
 * @internal
 */
function functionWithInternalTag(): void {}

function functionWithoutTag(): void {} // ERROR Function|functionWithoutTag
