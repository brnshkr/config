<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\ApiOrInternalTag;

/**
 * @api
 */
interface InterfaceWithApiTag {}

/**
 * @internal
 */
interface InterfaceWithInternalTag {}

interface InterfaceWithoutTag {}
