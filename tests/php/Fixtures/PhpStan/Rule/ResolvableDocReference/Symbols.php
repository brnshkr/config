<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference;

const GLOBAL_LABEL = 'global';

interface TargetInterface {}

trait TargetTrait {}

enum TargetEnum
{
    case First;
}

function globalHelper(): string
{
    return 'helper';
}
