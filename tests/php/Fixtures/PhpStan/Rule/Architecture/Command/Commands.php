<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Architecture\Command;

/**
 * @internal
 *
 * Passes — an interface cannot extend the console command
 */
interface UserCommandInterface {}

/**
 * @internal
 *
 * Fails — a command must extend the console command
 */
final class SyncUserCommand {}
