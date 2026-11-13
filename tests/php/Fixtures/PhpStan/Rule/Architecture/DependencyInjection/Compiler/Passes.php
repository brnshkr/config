<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Architecture\DependencyInjection\Compiler;

/**
 * @internal
 *
 * Passes — an interface cannot implement `CompilerPassInterface`
 */
interface UserPassInterface {}

/**
 * @internal
 *
 * Fails — a compiler pass must implement `CompilerPassInterface`
 */
final class RegisterUserPass {}
