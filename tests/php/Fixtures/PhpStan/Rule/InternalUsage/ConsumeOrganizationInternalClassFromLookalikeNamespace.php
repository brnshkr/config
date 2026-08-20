<?php

declare(strict_types=1);

namespace Brnshkrish\Consumer;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\OrganizationInternalClass;

function consumeOrganizationInternalClassFromLookalikeNamespace(): void
{
    $object = new OrganizationInternalClass(); // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\OrganizationInternalClass` is internal to `Brnshkr` and must not be used from `Brnshkrish\Consumer`.
}
