<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\PublicApiDocumentation;

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

/**
 * Abstract class description.
 *
 * @api
 */
abstract class AbstractWithUnimplementedMethod
{
    /**
     * Abstract method, has description and `@param` prose. No `@example` required.
     *
     * @param non-empty-string $arg The argument
     */
    abstract public function abstractMethod(string $arg): void;
}

/**
 * Interface description — `@example` not required on interface methods.
 *
 * @api
 */
interface InterfaceWithMethod
{
    /**
     * Interface method description, with `@param` prose.
     *
     * @param non-empty-string $arg The argument
     */
    public function interfaceMethod(string $arg): void;
}

/**
 * Function description.
 *
 * @api
 *
 * @example
 * ```php
 * publicApiDocsGoodFunction('x');
 * ```
 *
 * @param non-empty-string $arg The argument
 */
function publicApiDocsGoodFunction(string $arg): void {}

/**
 * @api
 */
function publicApiDocsMissingDescriptionFunction(): void {}
