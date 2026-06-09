# `InternalUsageRule` [🔍](../../../../src/php/PhpStan/Rule/InternalUsageRule.php 'Go to source')

Symbols marked as `@internal` may only be used from within their own root namespace. Any attempt to reach into another package's internals is reported as a violation.

```php
namespace Acme\User\Internal;

/**
 * @internal
 */
final class PasswordHasher {}

// ❌ Bad — consumed from a different root namespace
namespace Acme\Email;
new \Acme\User\Internal\PasswordHasher();

// ✅ Good — consumed from inside the same root namespace
namespace Acme\User\Authentication;
new \Acme\User\Internal\PasswordHasher();
```

## Explicit `@internal` target

The `@internal` tag also accepts an optional FQCN or namespace argument (`@internal Acme\Foo\Bar`). It overrides the default "same root namespace" target — use it when an internal symbol lives in one namespace but should only be reachable from another.

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

## Options

Three configuration options widen what counts as a legal caller:

- `allowedCallingNamespaces` — entries matched against the caller's namespace. Useful for letting test suites or other infrastructure reach into internals
- `allowedDeclaringNamespaces` — entries matched against the namespace that declares the internal symbol. Useful for exempting whole packages from the check
- `allowedInternalTargets` — entries matched against the FQCN or namespace argument passed to `@internal`. Useful when many symbols share the same target and should all be reachable from anywhere

Each entry is either a plain namespace prefix (matches the exact namespace plus anything below it) or a `/.../`-delimited regex pattern for advanced cases. Plain prefixes avoid the four-backslash escaping single-quoted PHP strings require for regex `\\`-separators.

```php
use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\Rule\InternalUsageRule;

return PhpStan::getConfig(null, true)
    ->setRules([
        PhpStan::configureRule(InternalUsageRule::class, [
            'allowedCallingNamespaces'   => ['Acme\Tests'],
            'allowedDeclaringNamespaces' => ['Acme\Shared'],
            'allowedInternalTargets'     => ['/^Acme\\\\User$/'],
        ]),
    ])
    ->toArray()
;
```
