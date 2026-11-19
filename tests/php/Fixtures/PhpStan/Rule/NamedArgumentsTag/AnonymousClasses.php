<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\NamedArgumentsTag;

$anonymousClass = new class {
    public function anonymousMethod(): void {}
};

$anonymousClassWithParameters = new class {
    public function anonymousMethod(string $argument): void {}
};
