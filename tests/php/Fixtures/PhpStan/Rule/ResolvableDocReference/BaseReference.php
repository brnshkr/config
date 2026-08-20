<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference;

abstract class BaseReference
{
    /**
     * Subclasses provide the label: {@see static::label()}.
     */
    public function inherited(): string
    {
        return 'inherited';
    }

    abstract public function label(): string;
}
