<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\PublicApiDocumentation;

/**
 * Passing class — has a real description sentence here.
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class GoodClass
{
    /**
     * Returns a useful integer.
     *
     * @example
     * ```php
     * new GoodClass()->doWork('x');
     * ```
     *
     * @param non-empty-string $input The input string to process
     *
     * @return int Some count derived from the input
     */
    public static function doWork(string $input): int
    {
        return 1;
    }

    /**
     * Pure getter — no params, no `@example` required.
     *
     * @return string The current label
     */
    public function getLabel(): string
    {
        return 'x';
    }

    /**
     * @internal Skipped because of the internal tag.
     */
    public function internalMethod(string $arg): void {}
}
