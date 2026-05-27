# `BoolishPrefixRule` [🔍](../../../../src/php/PhpStan/Rule/BoolishPrefixRule.php 'Go to source')

Boolean variables, parameters, properties, constants, and return values must start with one of the recognized boolish prefixes (`is`, `has`, `can`, `does`, and so on). The intent is that boolean-ness is always obvious from the name alone, both at the call site and at the declaration.

```php
final class User
{
    // ❌ Bad — property and method without a boolish prefix
    public bool $active;
    public function verified(): bool {}

    // ✅ Good
    public bool $isActive;
    public function isVerified(): bool {}
}
```

The check applies to every boolean-typed declaration, not just class members:

```php
// ❌ Bad — function parameter, function return, and global constant
function sendWelcomeEmail(User $user, bool $retry): void {}
function admin(User $user): bool {}

const WELCOME_EMAIL_ENABLED = true;

// ✅ Good
function sendWelcomeEmail(User $user, bool $doRetry): void {}
function isAdmin(User $user): bool {}

const IS_WELCOME_EMAIL_ENABLED = true;
```
