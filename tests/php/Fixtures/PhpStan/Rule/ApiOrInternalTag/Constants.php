<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ApiOrInternalTag;

/**
 * @api
 */
const CONSTANT_WITH_API_TAG = 'foo';

/**
 * @internal
 */
const CONSTANT_WITH_INTERNAL_TAG = 'bar';

const CONSTANT_WITHOUT_TAG_A = 'baz'; // ERROR Constant|CONSTANT_WITHOUT_TAG_A
const CONSTANT_WITHOUT_TAG_B = 'qux'; // ERROR Constant|CONSTANT_WITHOUT_TAG_B
