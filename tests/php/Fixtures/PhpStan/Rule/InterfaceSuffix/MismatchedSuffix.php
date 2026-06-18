<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InterfaceSuffix;

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
 * Fails — implements `UserServiceInterface` but class name does not end with `UserService`
 */
final class User implements UserServiceInterface // ERROR Class `User` implements `UserServiceInterface` and must end with suffix `UserService`.
{
    public function create(string $email): void {}
}

/**
 * @internal
 *
 * Fails — implements `EventSubscriberInterface` but class name does not end with `EventSubscriber`
 */
final class BadListener implements EventSubscriberInterface // ERROR Class `BadListener` implements `EventSubscriberInterface` and must end with suffix `EventSubscriber`.
{
    public function onEvent(): void {}
}
