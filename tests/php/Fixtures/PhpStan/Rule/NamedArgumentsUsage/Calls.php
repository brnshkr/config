<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NamedArgumentsUsage;

/**
 * @named-arguments
 */
final class Options
{
    public function __construct(
        public string $first = '',
        public string $second = '',
    ) {}

    public static function create(string $first = '', string $second = ''): self
    {
        return new self(
            first: $first,
            second: $second,
        );
    }

    public function with(string $first): self
    {
        return new self(
            first: $first,
            second: $this->second,
        );
    }
}

/**
 * @no-named-arguments
 */
final class Positional
{
    public function __construct(
        public string $first = '',
    ) {}

    public function handle(string $argument): void {}
}

/**
 * @internal
 *
 * @named-arguments
 */
final class InternalOptions
{
    public function __construct(
        public string $first = '',
    ) {}
}

/**
 * @internal
 *
 * @no-named-arguments
 */
final class InternalPositional
{
    public function __construct(
        public string $first = '',
    ) {}
}

/**
 * @named-arguments
 */
final class Overriding
{
    /**
     * @no-named-arguments
     */
    public function exempt(string $argument): void {}

    public function governed(string $argument): void {}
}

/**
 * @no-named-arguments
 */
final class OverridingPositional
{
    /**
     * @named-arguments
     */
    public function strict(string $argument): void {}

    public function relaxed(string $argument): void {}
}

final class Caller
{
    public function good(): void
    {
        new Options(first: 'a', second: 'b');
        Options::create(first: 'a');
        new Options()->with(first: 'a');
        new Positional('a');
        new Positional()->handle('a');
        new InternalPositional('a');
        new Overriding()->exempt('a');
        new OverridingPositional()->relaxed('a');
    }

    public function bad(): void
    {
        new Options('a', 'b'); // ERROR Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NamedArgumentsUsage\Options::__construct()
        Options::create('a'); // ERROR Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NamedArgumentsUsage\Options::create()
        new Options()->with('a'); // ERROR Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NamedArgumentsUsage\Options::with()
        new InternalOptions('a'); // ERROR Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NamedArgumentsUsage\InternalOptions::__construct()
        new Overriding()->governed('a'); // ERROR Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NamedArgumentsUsage\Overriding::governed()
        new OverridingPositional()->strict('a'); // ERROR Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NamedArgumentsUsage\OverridingPositional::strict()
    }

    public function partiallyNamed(): void
    {
        new Options('a', second: 'b'); // ERROR Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NamedArgumentsUsage\Options::__construct()
    }
}
