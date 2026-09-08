# `InternalUsageRule` [🔍](../../../../src/php/PhpStan/Rule/InternalUsageRule.php 'Go to source')

Symbols marked as `@internal` may only be used from within their own declaring namespace or a sub-namespace of it.
Any attempt to reach into another package's internals is reported as a violation.

```php
namespace Acme\User\Internal;

/**
 * @internal
 */
final class PasswordHasher {}

// ❌ Bad — consumed from another package
namespace Acme\Email;
new \Acme\User\Internal\PasswordHasher();

// ❌ Bad — a sibling namespace sits outside the declaring subtree
namespace Acme\User\Authentication;
new \Acme\User\Internal\PasswordHasher();

// ✅ Good — consumed from below the declaring namespace
namespace Acme\User\Internal\Hashing;
new \Acme\User\Internal\PasswordHasher();
```

## Explicit `@internal` target

The `@internal` tag also accepts an optional FQCN or namespace argument (`@internal Acme\User`).
It replaces the declaring namespace as the reachable subtree
— use it when an internal symbol lives in one namespace but should only be reachable from another.

```php
namespace Acme\Shared;

/**
 * @internal Acme\User
 */
final class UserOnlyHelper {}

// ❌ Bad — caller is not inside the declared @internal target
namespace Acme\Email;
new \Acme\Shared\UserOnlyHelper();

// ✅ Good — caller sits inside the declared @internal target
namespace Acme\User\Authentication;
new \Acme\Shared\UserOnlyHelper();
```

A bare vendor namespace (`@internal Acme`) widens that to every sibling package of the same organization.

Anything after `@internal` that is not a single namespace — `@internal invoked by the framework`
— counts as a description and leaves a plain `@internal`.

## Options

There is one list per side of the pair:

| Option | Names | Matched against |
| --- | --- | --- |
| `allowedCallers` | who is reaching | the caller's namespace |
| `allowedInternals` | what is being reached | the `@internal` target, the declaring namespace, or the symbol |

An entry is a plain prefix, matching that value and anything below it, or a regular expression such as `/^Acme/`.
The three values `allowedInternals` matches nest, so a namespace covers every symbol in it; anchor a regular
expression to mean only one, as `/^Acme$/` does.

A bare entry exempts its subject from every internal, everywhere. A list bounds it to the other side of the pair:

```php
'allowedCallers' => [
    'Acme\Console',                    // may reach any internal at all
    'Acme\Reporting' => ['Acme\User'], // may reach only Acme\User's internals
],
```

Your own test suite needs none of this: every namespace in `autoload-dev` already reaches the internals
of the namespace in `autoload`, and nothing else.

The rule ships already registered, so drop the default registration before adding your own or it runs twice
and reports every violation twice.

```php
use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\Rule\InternalUsageRule;

return PhpStan::getConfig(null, true)
    ->removeRules([InternalUsageRule::class])
    ->setRules([
        PhpStan::configureRule(InternalUsageRule::class, [
            'allowedCallers'   => ['Acme\Console'],
            'allowedInternals' => ['Acme\User\Internal\Hasher::hash()' => ['Acme\Security']],
        ]),
    ])
    ->toArray()
;
```
