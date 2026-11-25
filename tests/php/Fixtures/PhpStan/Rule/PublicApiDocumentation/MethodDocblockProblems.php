<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\PublicApiDocumentation;

/**
 * Class description present.
 *
 * @api
 */
final class MethodDocblockProblems
{
    /**
     * @param non-empty-string $arg The argument
     */
    public function missingDescription(string $arg): void {}

    /**
     * Method has description.
     *
     * @example
     * ```php
     * new MethodDocblockProblems()->missingParamProse('x');
     * ```
     *
     * @param non-empty-string $arg
     */
    public function missingParamProse(string $arg): void {}

    /**
     * Method has description.
     *
     * @example
     * ```php
     * new MethodDocblockProblems()->missingReturnProse();
     * ```
     */
    public function missingReturnProse(): int
    {
        return 0;
    }

    /**
     * Method has description.
     *
     * @param non-empty-string $arg The argument
     */
    public function missingExample(string $arg): void {}

    /**
     * Method has description.
     *
     * @example
     * ```php
     * new MethodDocblockProblems()->voidNoReturnTagOk();
     * ```
     *
     * @param non-empty-string $arg The argument
     */
    public function voidNoReturnTagOk(string $arg): void {}
}
