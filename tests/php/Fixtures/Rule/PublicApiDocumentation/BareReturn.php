<?php

/**
 * @api
 */

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule;

/**
 * Properly documented return target.
 *
 * @api
 */
final readonly class BareReturnTarget
{
    /**
     * Builds a target instance.
     *
     * @api
     *
     * @example
     * ```php
     * BareReturnTarget::build('x');
     * ```
     *
     * @param non-empty-string $label Caller-facing label
     *
     * @return self Constructed target
     */
    public static function build(string $label): self
    {
        return new self();
    }
}

return BareReturnTarget::build('foo');
