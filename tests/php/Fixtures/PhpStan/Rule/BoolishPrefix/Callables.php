<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\BoolishPrefix;

use Closure;

/**
 * @internal
 */
final class BoolishPrefixCallablesFixture
{
    /**
     * @var Closure(string): bool
     */
    public Closure $matchesName;

    /**
     * @var Closure(string): bool
     */
    public Closure $nameFilter; // ERROR missing-predicate|Property|nameFilter

    /**
     * @var bool
     */
    public $enabled; // ERROR missing|Property|enabled

    /**
     * @var Closure(bool $isShort): string
     */
    public Closure $labeler;

    /**
     * @var Closure(bool $short): string
     */
    public Closure $formatter; // ERROR missing|Parameter|short

    /**
     * @param Closure(): bool $accepts
     */
    public function __construct(
        public Closure $accepts,
    ) {}

    /**
     * @param callable(string): bool $isKnown
     * @param callable(string): bool $checker
     * @param callable $isLoose
     * @param bool $flag
     * @param callable(bool $isShort): string $formatter
     * @param callable(bool $short): string $renderer
     */
    public function run(
        callable $isKnown,
        callable $checker, // ERROR missing-predicate|Parameter|checker
        callable $isLoose, // ERROR reserved|Parameter|isLoose|is
        $flag, // ERROR missing|Parameter|flag
        callable $formatter,
        callable $renderer, // ERROR missing|Parameter|short
    ): void {
        $isAllowed = static fn (string $name): bool => $name !== '';
        $filter    = static fn (string $name): bool => $name !== ''; // ERROR missing-predicate|Variable|filter
    }

    /**
     * @return bool
     */
    public function check($value) // ERROR missing|Method|check
    {
        return $value !== null;
    }
}
