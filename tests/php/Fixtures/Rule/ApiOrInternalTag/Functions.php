<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\ApiOrInternalTag;

/**
 * @api
 */
function functionWithApiTag(): void {}

/**
 * @internal
 */
function functionWithInternalTag(): void {}

function functionWithoutTag(): void {}
