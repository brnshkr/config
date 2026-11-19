<?php

declare(strict_types=1);

namespace Brnshkr\Sibling\Package;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\OrganizationInternalClass;

function consumeOrganizationInternalClass(): void
{
    $object = new OrganizationInternalClass();
}
