<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal;

/**
 * @internal
 */
const INTERNAL_CONSTANT = 1;

const PUBLIC_CONSTANT = 2;

/**
 * @internal
 */
define('Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\DEFINED_INTERNAL_CONSTANT', 3);

/**
 * @internal
 */
ini_set('precision', '14');
