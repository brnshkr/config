# `NoNamedArgumentsTagRule` [🔍](../../../../src/php/PhpStan/Rule/NoNamedArgumentsTagRule.php 'Go to source')

Symbols exposed as `@api` must additionally carry a `@no-named-arguments` tag.
This keeps parameter names out of the backward-compatibility contract,
leaving them free to be renamed without breaking the public-facing API.

```php
// ❌ Bad — '@api' alone leaks parameter names into the public contract
/**
 * @api
 */
final class UserService {}

// ✅ Good
/**
 * @api
 *
 * @no-named-arguments
 */
final class UserService {}
```

The check applies to every `@api` symbol — top-level functions and traits included:

```php
// ❌ Bad — '@api' function with no '@no-named-arguments'
/**
 * @api
 */
function formatUser(User $user, bool $isShort): string {}

// ✅ Good
/**
 * @api
 *
 * @no-named-arguments
 */
function formatUser(User $user, bool $isShort): string {}

// ❌ Bad — '@api' trait without '@no-named-arguments'
/**
 * @api
 */
trait EmailAddressTrait {}

// ✅ Good
/**
 * @api
 *
 * @no-named-arguments
 */
trait EmailAddressTrait {}
```
