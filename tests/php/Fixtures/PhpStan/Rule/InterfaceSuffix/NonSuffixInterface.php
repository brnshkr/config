<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InterfaceSuffix;

use Stringable;

/**
 * @internal
 *
 * Marker — bare "Interface" name, imposes no suffix requirement
 */
interface Interface_
{
    public function noop(): void;
}

/**
 * @internal
 *
 * Passes — `Stringable` does not end with `Interface`, no constraint imposed
 */
final class StringableHolder implements Stringable
{
    public function __toString(): string
    {
        return '';
    }
}
