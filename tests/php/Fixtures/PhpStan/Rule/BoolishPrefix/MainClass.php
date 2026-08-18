<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\BoolishPrefix;

/**
 * @internal
 */
final class BoolishPrefixFixtureClass
{
    public const bool IS_VALID = true;
    public const bool ENABLED  = false; // ERROR missing|Constant|ENABLED

    public bool $isActive = true;

    public bool $active = false; // ERROR missing|Property|active

    public bool $supportsHttps = true;

    public bool $expectsJson = true;

    public bool $allowsNull = false; // relational verb is a valid bool flag on a value

    public function __construct(
        public bool $isReady = true,
        public bool $ready = false, // ERROR missing|Property|ready
        bool $isEnabled = true,
        bool $enabled = false, // ERROR missing|Parameter|enabled
    ) {}

    public function isValid(): bool
    {
        return true;
    }

    public function validate(): bool // ERROR missing|Method|validate
    {
        return true;
    }

    public function hasItems(): bool
    {
        return true;
    }

    public function checkItems(): bool // ERROR missing|Method|checkItems
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

    public function toBoolean(): bool
    {
        return true;
    }

    public function asArray(): bool // ERROR missing|Method|asArray
    {
        return true;
    }

    public function getStatus(): string
    {
        return 'ok';
    }

    public function process(bool $isForced, bool $forced, bool $asArray): void {} // ERROR missing|Parameter|forced

    public function island( // ERROR missing|Method|island
        bool $assigned, // ERROR missing|Parameter|assigned
    ): bool {
        return $assigned;
    }

    public function containsKey(): bool
    {
        return true;
    }

    public function expectsInput(): bool
    {
        return true;
    }

    public function coversRange(): bool
    {
        return true;
    }

    public function intersectsWith(): bool
    {
        return true;
    }

    public function withClosures(): void
    {
        $checker = static fn (bool $force): bool => $force; // ERROR missing|Parameter|force
        $runner  = static function (bool $isForced): void {};
    }
}
