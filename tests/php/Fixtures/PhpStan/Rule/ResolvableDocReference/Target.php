<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference;

final class Target
{
    public const string LABEL = 'label';

    public string $name = '';

    public static function build(): self
    {
        return new self();
    }
}
