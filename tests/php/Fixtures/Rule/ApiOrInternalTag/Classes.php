<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\ApiOrInternalTag;

/**
 * @api
 */
final class ClassWithApiTag {}

/**
 * @internal
 */
final class ClassWithInternalTag {}

final class ClassWithoutTag {}

$anonymousClass = new class {};
