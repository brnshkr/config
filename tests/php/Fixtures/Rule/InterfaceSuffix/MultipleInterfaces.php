<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\InterfaceSuffix;

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
 *
 * Skipped — class implements two `*Interface`-suffixed interfaces, so the rule cannot
 * pick a single expected suffix and bails out (no error reported).
 */
final class UserRepository implements
    RepositoryInterface,
    UserServiceInterface
{
    public function find(int $id): ?object
    {
        return null;
    }

    public function create(string $email): void {}
}
