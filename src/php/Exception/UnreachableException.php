<?php

declare(strict_types=1);

namespace Brnshkr\Config\Exception;

use LogicException;
use Throwable;

/**
 * @internal Brnshkr\Config
 */
final class UnreachableException extends LogicException
{
    public static function wrap(Throwable $throwable): self
    {
        return $throwable instanceof self
            ? $throwable
            : new self(
                message: $throwable->getMessage(),
                code: (int) $throwable->getCode(),
                previous: $throwable,
            );
    }
}
