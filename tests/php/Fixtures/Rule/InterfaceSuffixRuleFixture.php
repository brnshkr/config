<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\InterfaceSuffix;

use Stringable;

/**
 * @internal
 */
interface RepositoryInterface
{
    public function find(int $id): ?object;
}

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
 * Marker — bare "Interface" name, imposes no suffix requirement.
 */
interface Interface_
{
    public function noop(): void;
}

/**
 * @internal
 *
 * Passes — class name ends with the expected `Repository` suffix.
 */
final class UserRepository implements RepositoryInterface
{
    public function find(int $id): ?object
    {
        return null;
    }
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

/**
 * @internal
 *
 * Passes — `Stringable` does not end with `Interface`, no constraint imposed.
 */
final class StringableHolder implements Stringable
{
    public function __toString(): string
    {
        return '';
    }
}

/**
 * @internal
 *
 * Skipped — class implements two `*Interface`-suffixed interfaces, so the rule cannot
 * pick a single expected suffix and bails out (no error reported).
 */
final class BlogRepository implements
    RepositoryInterface,
    UserServiceInterface
{
    public function find(int $id): ?object
    {
        return null;
    }

    public function create(string $email): void {}
}
