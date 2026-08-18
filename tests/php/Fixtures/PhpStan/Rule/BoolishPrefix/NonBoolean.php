<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\BoolishPrefix;

/**
 * @internal
 */
final class BoolishPrefixNonBooleanFixture
{
    public const string IS_LABEL = 'x'; // ERROR reserved|Constant|IS_LABEL|is

    public int $hasCount = 0; // ERROR reserved|Property|hasCount|has

    public array $matches = [];

    public int $startsAt = 0;

    public int $expectsCount = 0; // ERROR reserved|Property|expectsCount|expects

    public array $coversList = []; // ERROR reserved|Property|coversList|covers

    public function __construct(
        public string $isName = '', // ERROR reserved|Property|isName|is
        string $shouldLabel = '', // ERROR reserved|Parameter|shouldLabel|should
    ) {}

    public function hasName(): string // ERROR reserved|Method|hasName|has
    {
        return $this->isName;
    }

    public function doRun(): void {}

    public function requiresList(): array // ERROR reserved|Method|requiresList|requires
    {
        return [];
    }

    public function asArray(): array
    {
        return [];
    }

    public function allowsAccess(): array // ERROR reserved|Method|allowsAccess|allows
    {
        return [];
    }

    public function expectsList(): array // ERROR reserved|Method|expectsList|expects
    {
        return [];
    }

    public function coversArea(): array // ERROR reserved|Method|coversArea|covers
    {
        return [];
    }

    public function intersectsRange(): array // ERROR reserved|Method|intersectsRange|intersects
    {
        return [];
    }

    public function startsWith(): array // ERROR reserved|Method|startsWith|starts
    {
        return [];
    }

    public function asBoolean(): string // ERROR reserved|Method|asBoolean|asBoolean
    {
        return 'x';
    }
}
