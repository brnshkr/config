# `PublicApiDocumentationRule` [🔍](../../../../src/php/PhpStan/Rule/PublicApiDocumentationRule.php 'Go to source')

Symbols marked as `@api` must carry a docblock that explains them in prose. Every class, interface, trait, enum,
top-level function, and non-private method on an `@api` class has to have a real description before the first PHPDoc tag,
an `@param` line per parameter with a description after the variable name, an `@return` line that describes what the value
represents when the symbol returns something non-void, and an `@example` block whenever calling it involves arguments.
The aim is that anyone landing on a public symbol gets the same level of guidance no matter where in the codebase it lives.

```php
// ❌ Bad — '@api' class with no description and an undocumented parameter
/**
 * @api
 *
 * @no-named-arguments
 */
final class UserService
{
    public function rename(User $user, string $name): User {}
}

// ✅ Good
/**
 * Coordinates user-account mutations.
 *
 * @api
 *
 * @no-named-arguments
 */
final class UserService
{
    /**
     * Renames the given user.
     *
     * @param User $user the user being renamed
     * @param string $name new display name, trimmed and validated against the username policy
     *
     * @return User the freshly persisted user, reloaded from the database
     *
     * @example
     * ```php
     * $userService->rename($user, 'Ada Lovelace');
     * ```
     */
    public function rename(User $user, string $name): User {}
}
```

Top-level `@api` functions are held to the same standard:

```php
// ❌ Bad — '@api' function with no description and an undocumented parameter
/**
 * @api
 *
 * @no-named-arguments
 */
function formatUser(User $user, bool $isShort): string {}

// ✅ Good
/**
 * Formats a user for display in a listing.
 *
 * @param User $user the user being rendered
 * @param bool $isShort whether to collapse the result to a single line
 *
 * @return string the rendered, escape-safe display string
 *
 * @example
 * ```php
 * formatUser($user, false);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
function formatUser(User $user, bool $isShort): string {}
```

## File-level docblock

A file-level docblock (the first `/** ... */` before `namespace`, `declare`, or `use`) sets the default visibility
for the file, and this rule follows it. An `@api` there holds every untagged symbol to the standard,
and an `@internal` there exempts the file. [`ApiOrInternalTagRule`](./ApiOrInternalTagRule.md#file-level-shortcut)
is the source of truth for how that docblock is found.

```php
/**
 * @api
 */

namespace Acme\User;

// ❌ Bad — the file-level '@api' reaches this class, so the missing description is reported
final class UserService {}

// ✅ Good — an explicit '@internal' opts a single class back out
/**
 * @internal
 */
final class PasswordHasher {}
```

A top-level `return` in an `@api` file needs a description too, either on the `return` statement or on the value
it returns. A `new ClassName(...)` falls back to the class docblock and a `Class::method(...)` to the method docblock,
which is how a config-style file documents itself through the type it returns.

```php
/**
 * @api
 */

// ✅ Good — the description sits on the 'return' itself
/**
 * Rule set shipped to consumers of this package.
 */
return $config;
```

## Exemptions

- **Private methods, and methods tagged `@internal`.** Nothing outside reaches them.
- **Anonymous classes.** They have no name to import, and no caller beyond the expression building them.
- **Constructors.** The class docblock already covers the type's purpose, so they need no description of their own.
- **Fluent setters returning `self` or `static`.** The return type says everything an `@return` or `@example` would.
- **Interface and abstract methods.** They skip `@example`, having no implementation to demonstrate.
- **Methods an ancestor already documents**, whether they say so with `@inheritDoc` or stay silent.

`@throws` coverage is left to PHPStan's built-in throw-type checks.

The JavaScript counterpart is [`brnshkr/public-api-documentation`](../../../js/eslint/rules/public-api-documentation.md).
