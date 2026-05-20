<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\BoolishPrefix;

/**
 * @internal
 */
final class BoolishPrefixFixtureClass
{
    public const bool IS_VALID = true;
    public const bool ENABLED  = false;

    public bool $isActive = true;

    public bool $active = false;

    public function __construct(
        public bool $isReady = true,
        public bool $ready = false,
        bool $isEnabled = true,
        bool $enabled = false,
    ) {}

    public function isValid(): bool
    {
        return true;
    }

    public function validate(): bool
    {
        return true;
    }

    public function hasItems(): bool
    {
        return true;
    }

    public function checkItems(): bool
    {
        return false;
    }

    public function canAccess(): bool
    {
        return true;
    }

    public function doesExist(): bool
    {
        return true;
    }

    public function wasDeleted(): bool
    {
        return true;
    }

    public function didSucceed(): bool
    {
        return true;
    }

    public function doProcess(): bool
    {
        return true;
    }

    public function asBoolean(): bool
    {
        return true;
    }

    public function getStatus(): string
    {
        return 'ok';
    }

    public function process(bool $isForced, bool $forced): void {}
}
