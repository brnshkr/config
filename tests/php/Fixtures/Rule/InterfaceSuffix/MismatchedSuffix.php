<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\InterfaceSuffix;

/**
 * @internal
 */
interface UserServiceInterface
{
    public function create(string $email): void;
}

/**
 * @internal
 */
interface EventSubscriberInterface
{
    public function onEvent(): void;
}

/**
 * @internal
 *
 * Fails — implements `UserServiceInterface` but class name does not end with `UserService`.
 */
final class User implements UserServiceInterface
{
    public function create(string $email): void {}
}

/**
 * @internal
 *
 * Fails — implements `EventSubscriberInterface` but class name does not end with `EventSubscriber`.
 */
final class BadListener implements EventSubscriberInterface
{
    public function onEvent(): void {}
}
