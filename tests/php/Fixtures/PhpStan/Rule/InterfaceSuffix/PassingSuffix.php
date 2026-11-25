<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InterfaceSuffix;

/**
 * @internal
 */
interface RepositoryInterface
{
    public function find(int $id): ?object;
}

/**
 * @internal
 *
 * Passes — class name ends with the expected `Repository` suffix
 */
final class UserRepository implements RepositoryInterface
{
    public function find(int $id): ?object
    {
        return null;
    }
}
