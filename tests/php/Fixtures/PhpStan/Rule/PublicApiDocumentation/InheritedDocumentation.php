<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\PublicApiDocumentation;

/**
 * The contract that documents the method once.
 *
 * @api
 */
interface DocumentedContract
{
    /**
     * Converts one document.
     *
     * @param non-empty-string $markdown The document
     *
     * @return non-empty-string The HTML it becomes
     */
    public function convert(string $markdown): string;
}

/**
 * Satisfies the contract without repeating its docblock.
 *
 * @api
 */
final class InheritsDocumentation implements DocumentedContract
{
    public function convert(string $markdown): string
    {
        return $markdown;
    }
}

/**
 * Satisfies the contract and says so explicitly.
 *
 * @api
 */
final class SaysInheritDoc implements DocumentedContract
{
    /**
     * @inheritDoc
     */
    public function convert(string $markdown): string
    {
        return $markdown;
    }
}

/**
 * Declares a method of its own, which nothing documents for it.
 *
 * @api
 */
final class UndocumentedOwnMethod
{
    public function own(string $argument): string
    {
        return $argument;
    }
}

/**
 * The contract whose method carries no prose.
 *
 * @internal
 */
interface UndocumentedContract
{
    public function render(string $markdown): string;
}

/**
 * Implements a contract that documents nothing.
 *
 * @api
 */
final class InheritsNothing implements UndocumentedContract
{
    public function render(string $markdown): string
    {
        return $markdown;
    }
}

/**
 * Says so where nothing above it documents anything.
 *
 * @api
 */
final class SaysInheritDocWithoutProse implements UndocumentedContract
{
    /**
     * @inheritDoc
     */
    public function render(string $markdown): string
    {
        return $markdown;
    }
}
