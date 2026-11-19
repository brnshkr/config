<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ApiOrInternalTag;

/**
 * @api
 */
final class ClassWithApiTag {}

/**
 * @internal
 */
final class ClassWithInternalTag {}

final class ClassWithoutTag {} // ERROR Class|ClassWithoutTag

$anonymousClass = new class {};
