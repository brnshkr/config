# `InternalUsageRule` [🔍](../../../../src/php/PhpStan/Rule/InternalUsageRule.php 'Go to source')

Symbols marked as `@internal` may only be used from within their own declaring namespace or a sub-namespace of it. Any attempt to reach into another package's internals is reported as a violation.

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

The `@internal` tag also accepts an optional FQCN or namespace argument (`@internal Acme\User`). It replaces the declaring namespace as the reachable subtree — use it when an internal symbol lives in one namespace but should only be reachable from another.

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

Anything after `@internal` that is not a single namespace — `@internal invoked by the framework` — counts as a description and leaves a plain `@internal`.

## Options

Four configuration options widen what counts as a legal caller:

- `allowedCallingNamespaces` — entries matched against the caller's namespace. Useful for letting test suites or other infrastructure reach into internals
- `allowedDeclaringNamespaces` — entries matched against the namespace that declares the internal symbol. Useful for exempting whole packages from the check
- `allowedInternalTargets` — entries matched against the FQCN or namespace argument passed to `@internal`. Useful when many symbols share the same target and should all be reachable from anywhere
- `allowedSymbols` — entries matched against the fully-qualified name of the symbol being used, whether that is a class, enum, interface, trait, function or one of their members. Useful for exempting a single symbol without opening its namespace

Each entry is either a plain prefix, which matches that value and anything below it, or a regular expression such as `/^Acme/`.

The rule ships already registered, so drop the default registration before adding your own or it runs twice and reports every violation twice.

```php
use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\Rule\InternalUsageRule;

return PhpStan::getConfig(null, true)
    ->removeRules([InternalUsageRule::class])
    ->setRules([
        PhpStan::configureRule(InternalUsageRule::class, [
            'allowedCallingNamespaces'   => ['Acme\Tests'],
            'allowedDeclaringNamespaces' => ['/^Acme\\\Shared/'],
            'allowedInternalTargets'     => ['/^Acme\\\User$/'],
            'allowedSymbols'             => ['Acme\User\Internal\PasswordHasher::hash()'],
        ]),
    ])
    ->toArray()
;
```
