<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\PublicApiDocumentation;

/**
 * @api
 */
final class MissingClassDescription
{
    /**
     * Method has full docblock.
     *
     * @example
     * ```php
     * new MissingClassDescription()->run('x');
     * ```
     *
     * @param non-empty-string $arg The argument
     */
    public function run(string $arg): void {}
}
