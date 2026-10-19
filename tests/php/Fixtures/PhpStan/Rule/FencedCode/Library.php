<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode;

/**
 * @no-named-arguments
 */
final class Builder
{
    public const string NAME = 'builder';

    public static function create(): self
    {
        return new self();
    }

    public function addPath(string $path, bool $isRecursive = true): self
    {
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return [];
    }
}

/**
 * @method static self tagged()
 */
final class Magic
{
    public static function __callStatic(string $name, array $arguments): self
    {
        return new self();
    }

    public function __call(string $name, array $arguments): self
    {
        return $this;
    }
}

/**
 * @named-arguments
 */
final class Preset
{
    /**
     * @param list<string> $modules
     *
     * @return array<string, mixed>
     */
    public static function forRoot(string $root, array $modules = []): array
    {
        return [];
    }
}
