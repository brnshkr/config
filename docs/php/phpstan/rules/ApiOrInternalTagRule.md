# `ApiOrInternalTagRule` [🔍](../../../../src/php/PhpStan/Rule/ApiOrInternalTagRule.php 'Go to source')

Every class, interface, trait, enum, top-level function, and global constant must declare its intended visibility
by carrying either an `@api` or an `@internal` tag in its docblock. The aim is to make the public surface
of every package an explicit, deliberate decision rather than an accident of which symbols happened to be reachable.

```php
// ❌ Bad — no visibility tag
final class UserService {}

// ❌ Bad — declares two visibilities at once
/**
 * @api
 * @internal
 */
final class UserService {}

// ✅ Good — '@api' marks the symbol as part of the public surface
/**
 * @api
 */
final class UserService {}

// ✅ Good — '@internal' marks the symbol as not for outside consumption
/**
 * @internal
 */
final class PasswordHasher {}
```

The check applies to top-level functions and global constants too:

```php
// ❌ Bad — no visibility tag on a top-level function
function formatUser(User $user): string {}

// ❌ Bad — no visibility tag on a global constant
const DEFAULT_EMAIL_TIMEOUT = 30;

// ✅ Good
/**
 * @api
 */
function formatUser(User $user): string {}

/**
 * @internal
 */
const DEFAULT_EMAIL_TIMEOUT = 30;
```

## File-level shortcut

A file-level docblock (the first `/** ... */` before `namespace`, `declare`, or `use`) carrying `@api` or `@internal`
sets the default visibility for every symbol declared below it, so files that are uniformly public
or uniformly internal do not need a tag on each declaration.

```php
/**
 * @internal
 */

namespace Acme\User\Internal;

// ✅ Good — both classes are covered by the file-level '@internal'
final class PasswordHasher {}
final class UsernameNormalizer {}
```

The same docblock also covers top-level `return` statements in config-style files that return a value.

## Exemptions

- **Anonymous classes.** They have no name to import, and no caller beyond the expression building them.

The JavaScript counterpart is [`brnshkr/api-or-internal-tag`](../../../js/eslint/rules/api-or-internal-tag.md).
