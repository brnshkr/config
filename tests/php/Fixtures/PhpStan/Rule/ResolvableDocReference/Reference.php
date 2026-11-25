<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference;

use Brnshkr\Config\Str;

/**
 * Resolves {@see Target} from this namespace and {@see Str} through the import.
 *
 * A class docblock resolves {@see self::describe()} against the class being declared.
 *
 * The `doc://` scheme is a complete URI: {@see doc://getting-started/index}.
 *
 * A lowercase class still resolves: {@see lowercaseTarget} and {@see lowercaseTarget::LABEL}.
 *
 * @see https://example.com/reference
 * @see Target
 * @see the registration flow
 * @link https://example.com/handbook
 */
final class Reference extends BaseReference
{
    public const string LABEL = 'label';

    public string $name = '';

    /**
     * Resolves {@see \Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\Target}.
     *
     * @see Target::LABEL
     */
    public function label(): string
    {
        return self::LABEL;
    }

    public function describe(): string
    {
        return Target::build()->name . Target::LABEL;
    }

    /**
     * Resolves {@see Target::$name} and {@see Target::build()}.
     *
     * {@see static::describe()} // ERROR Method `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\Reference::describe()` must be referenced by `self`, not `static`.
     * {@see static::inherited()} // ERROR Method `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\BaseReference::inherited()` must be referenced by its declaring class, not `static`.
     */
    public function combine(): string
    {
        return $this->describe() . $this->inherited();
    }

    /**
     * Resolves {@see parent::inherited()} and {@see ::describe()} without a class part.
     *
     * A bare property is not a FQSEN, so {@see $name} is left alone.
     *
     * {@see self::inherited()} // ERROR Method `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\BaseReference::inherited()` must be referenced by its declaring class, not `self`.
     * {@see parent::describe()} // ERROR Method `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\BaseReference::describe()` does not exist.
     */
    public function delegate(): string
    {
        return $this->inherited();
    }

    /**
     * A global function resolves: {@see globalHelper()} and {@see \Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\globalHelper()}.
     *
     * A global constant resolves: {@see GLOBAL_LABEL} and {@see \Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\GLOBAL_LABEL}.
     *
     * Every class-like kind resolves: {@see TargetInterface}, {@see TargetTrait},
     * {@see TargetEnum} and {@see TargetEnum::First}.
     */
    public function resolveEveryKind(): string
    {
        return globalHelper() . GLOBAL_LABEL;
    }

    /**
     * {@see Missing} // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\Missing` does not exist.
     * {@see missingHelper} // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\missingHelper` does not exist.
     * {@see Absent a description after the target is ignored} // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\Absent` does not exist.
     * {@see LABEL} // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\LABEL` does not exist.
     * {@see Target::gone()} // ERROR Method `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\Target::gone()` does not exist.
     * {@see self::absent} // ERROR Constant `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\Reference::absent` does not exist.
     * {@see Target::LABEL()} // ERROR Method `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\Target::LABEL()` does not exist.
     * {@see Target::$build} // ERROR Property `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\Target::$build` does not exist.
     * {@see Target::build} // ERROR Constant `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\Target::build` does not exist.
     * {@see absentFunction()} // ERROR Function `absentFunction()` does not exist.
     * {@link AlsoMissing} // ERROR Target `AlsoMissing` must be an absolute URI.
     * {@see docs/php/index.md} // ERROR Target `docs/php/index.md` must be an absolute URI.
     * @see StillMissing // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ResolvableDocReference\StillMissing` does not exist.
     * @link LinkedMissing // ERROR Target `LinkedMissing` must be an absolute URI.
     */
    public function broken(): void {}
}
