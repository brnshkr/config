<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ApiOrInternalTag;

/**
 * @api
 */
enum EnumWithApiTag {}

/**
 * @internal
 */
enum EnumWithInternalTag {}

enum EnumWithoutTag {} // ERROR Enum|EnumWithoutTag
