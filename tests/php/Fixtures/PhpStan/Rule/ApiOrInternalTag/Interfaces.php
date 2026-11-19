<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ApiOrInternalTag;

/**
 * @api
 */
interface InterfaceWithApiTag {}

/**
 * @internal
 */
interface InterfaceWithInternalTag {}

interface InterfaceWithoutTag {} // ERROR Interface|InterfaceWithoutTag
