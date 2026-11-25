<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure;

use RuntimeException;

/**
 * @internal Brnshkr\Config\Tests
 */
final class Hidden {}

/**
 * @api
 */
final class Shown {}

/**
 * @internal Brnshkr\Config\Tests
 */
enum HiddenChoice
{
    case First;
}

/**
 * @api
 */
final class Facade
{
    /**
     * @api
     */
    public function returnsPublic(): Shown
    {
        return new Shown();
    }

    /**
     * @api
     */
    public function returnsHidden(): Hidden // ERROR Method `returnsHidden()`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\Hidden
    {
        return new Hidden();
    }

    /**
     * @api
     */
    public function acceptsHidden(Hidden $hidden): void {} // ERROR Method `acceptsHidden()`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\Hidden

    /**
     * @api
     */
    public function acceptsNullableHidden(?Hidden $hidden): void {} // ERROR Method `acceptsNullableHidden()`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\Hidden

    /**
     * @var list<Hidden>
     */
    public array $documentedWithHidden = []; // ERROR Property `$documentedWithHidden`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\Hidden

    public Shown $shown;

    public Hidden $firstHidden, // ERROR Property `$firstHidden`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\Hidden
        $secondHidden; // ERROR Property `$secondHidden`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\Hidden

    public const string SAFE = 'safe';

    public const HiddenChoice CHOSEN = HiddenChoice::First; // ERROR Constant `CHOSEN`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\HiddenChoice

    /**
     * @internal
     */
    public function internalMethodMayExposeIt(): Hidden
    {
        return new Hidden();
    }

    private function privateMethodMayExposeIt(): Hidden
    {
        return new Hidden();
    }
}

/**
 * @internal Brnshkr\Config\Tests
 */
final class Inside
{
    public function mayExposeIt(): Hidden
    {
        return new Hidden();
    }

    /**
     * @api
     */
    public function publishedFromInside(): Hidden // ERROR Method `publishedFromInside()`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\Hidden
    {
        return new Hidden();
    }
}

/**
 * @internal Brnshkr\Config\Tests
 */
final class HiddenException extends RuntimeException {}

/**
 * @api
 */
final class Thrower
{
    /**
     * @throws HiddenException
     */
    public function throwsHidden(): void {} // ERROR Method `throwsHidden()`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\HiddenException

    /**
     * @see Hidden
     * @link Hidden
     * @uses Hidden
     */
    public function referencesHiddenOnly(): void {}
}

/**
 * @internal Brnshkr\Config\Tests
 */
abstract class HiddenBase
{
    public function inheritedReturnsHidden(): Hidden
    {
        return new Hidden();
    }

    public function inheritedIsSafe(): Shown
    {
        return new Shown();
    }
}

/**
 * @api
 */
final class Leaf extends HiddenBase {} // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\Leaf`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\Hidden

/**
 * @api
 *
 * @method Hidden magicReturnsHidden()
 */
final class MagicMethod {} // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\MagicMethod`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\Hidden

/**
 * @api
 *
 * @property Hidden $magicHidden
 */
final class MagicProperty {} // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\MagicProperty`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\Hidden

/**
 * @api
 *
 * @property-read Hidden $magicHiddenRead
 */
final class MagicPropertyRead {} // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\MagicPropertyRead`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\Hidden

/**
 * @api
 *
 * @mixin Hidden
 */
final class MagicMixin {} // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\MagicMixin`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\Hidden

/**
 * @api
 */
final class SilentThrower
{
    public function throwsHiddenUndocumented(): void
    {
        throw new HiddenException(); // ERROR Method `throwsHiddenUndocumented()`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\HiddenException
    }

    /**
     * @throws HiddenException
     */
    public function throwsHiddenDocumented(): void // ERROR Method `throwsHiddenDocumented()`|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\InternalExposure\HiddenException
    {
        throw new HiddenException();
    }

    /**
     * @internal
     */
    public function internalMayThrowIt(): void
    {
        throw new HiddenException();
    }
}
