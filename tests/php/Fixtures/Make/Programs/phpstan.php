<?php

declare(strict_types=1);

use Brnshkr\Config\PhpStan\Rule\InternalUsageRule;

return [
    'includes'   => [__DIR__ . '/../../../../../conf/phpstan.dist.php'],
    'parameters' => [
        'ignoreErrors' => [
            ['identifier' => InternalUsageRule::getIdentifier()],
            ['identifier' => 'offsetAccess.notFound'],
            ['identifier' => 'symplify.forbiddenFuncCall'],
            ['identifier' => 'symplify.forbiddenNode'],
            ['identifier' => 'variable.undefined'],
        ],
    ],
];
