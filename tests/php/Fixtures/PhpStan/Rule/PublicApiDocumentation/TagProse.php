<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\PublicApiDocumentation;

use RuntimeException;

/**
 * Holds a value.
 *
 * @api
 *
 * @template TValue
 *
 * @property string $label
 */
final class TagProse
{
    /**
     * Reads the value.
     *
     * @param array{
     *     key: int,
     * } $shape - the shape
     *
     * @return array{
     *     key: int,
     * }
     *
     * @throws RuntimeException
     *
     * @example
     * ```php
     * $holder->read(['key' => 1]);
     * ```
     */
    public function read(array $shape): array
    {
        return $shape;
    }
}
