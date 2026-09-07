<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NamedArgumentsTag;

/**
 * @named-arguments
 */
final class ClassAcceptingNamedArguments
{
    public function __construct(
        public string $argument = '',
    ) {}
}

/**
 * @named-arguments
 */
final class ServiceAcceptingNamedArguments
{
    public function handle(string $argument): void {}
}

final class ClassStillRequiringTheTag // ERROR Class|ClassStillRequiringTheTag
{
    public function handle(string $argument): void {}
}

final class EveryMethodGoverned
{
    /**
     * @named-arguments
     */
    public function __construct(
        public string $first = '',
    ) {}

    /**
     * @no-named-arguments
     */
    public function handle(string $argument): void {}

    public function noParameters(): void {}
}

final class EveryMethodInternal
{
    /**
     * @internal
     */
    public function __construct(
        public string $first = '',
    ) {}

    /**
     * @internal
     */
    public function handle(string $argument): void {}
}

final class OneMethodUngoverned // ERROR Class|OneMethodUngoverned
{
    /**
     * @no-named-arguments
     */
    public function __construct(
        public string $first = '',
    ) {}

    public function handle(string $argument): void {}
}
