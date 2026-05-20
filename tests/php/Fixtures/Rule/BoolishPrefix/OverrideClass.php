<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\BoolishPrefix;

use Override;

/**
 * @internal
 */
class BoolishPrefixLockSourceParent
{
    public function __construct(public bool $value = true) {}

    public function getResult(bool $forced): bool
    {
        return $forced;
    }
}

/**
 * @internal
 */
final class BoolishPrefixOverrideFixture extends BoolishPrefixLockSourceParent
{
    public function __construct(public bool $value = false)
    {
        parent::__construct($value);
    }

    #[Override]
    public function getResult(bool $forced): bool
    {
        $result = true;

        return $result && $forced;
    }
}
