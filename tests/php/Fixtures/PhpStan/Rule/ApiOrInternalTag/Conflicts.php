<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ApiOrInternalTag;

/**
 * @api
 * @internal
 */
final class ClassWithBothTags {} // ERROR conflict|Class `ClassWithBothTags`

/**
 * @api
 * @internal
 */
function functionWithBothTags(): void {} // ERROR conflict|Function `functionWithBothTags`

/**
 * @api
 * @internal
 */
const CONSTANT_WITH_BOTH_TAGS = 'value'; // ERROR conflict|Constant `CONSTANT_WITH_BOTH_TAGS`
