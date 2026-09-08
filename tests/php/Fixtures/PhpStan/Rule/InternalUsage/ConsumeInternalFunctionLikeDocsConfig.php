<?php

declare(strict_types=1);

/**
 * @internal Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal
 */

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass;

use function Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\Scoped\scopedInternalFunction;

/**
 * @internal
 */
return [
    'value' => scopedInternalFunction(),
    'other' => new InternalClass(),
];
