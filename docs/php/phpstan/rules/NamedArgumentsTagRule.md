# `NamedArgumentsTagRule` [🔍](../../../../src/php/PhpStan/Rule/NamedArgumentsTagRule.php 'Go to source')

Every declaration that exposes parameters must state where it stands on named arguments,
with `@named-arguments` or `@no-named-arguments`.
PHP's named-argument syntax turns parameter names into part of the contract
the moment a caller writes `formatUser(user: $user)`, and the stance says whether that is allowed.

`@named-arguments` is the deliberate claim that the names _are_ the contract,
because a caller — or a dependency-injection container binding by name — writes them out.

```php
// ✅ Good — the names are the contract, so a container may bind them and a caller must write them
/**
 * @named-arguments
 */
final class UserService
{
    public function __construct(public string $locale = 'en') {}
}
```

`@no-named-arguments` is the opposite: it keeps the names out of the contract, leaving them free
to be renamed. Without either tag the names are part of the contract by accident, which is what
gets reported.

```php
// ❌ Bad — the parameter name is part of the contract by accident
final class UserService
{
    public function __construct(private readonly string $locale) {}
}

// ✅ Good
/**
 * @no-named-arguments
 */
final class UserService
{
    public function __construct(private readonly string $locale) {}
}
```

Top-level functions carry it too:

```php
// ❌ Bad
function formatUser(User $user, bool $isShort): string {}

// ✅ Good
/**
 * @no-named-arguments
 */
function formatUser(User $user, bool $isShort): string {}
```

## Where a class needs the tag

A method may declare its own stance, and then the class owes nothing for it.
The class-level tag is required only where a method would otherwise be left ungoverned:

```php
// ✅ Good — every method answers for itself
final class UserService
{
    /**
     * @no-named-arguments
     */
    public function rename(string $name): void {}

    /**
     * @internal
     */
    public function reset(string $reason): void {}
}
```

The same resolution governs the calls, so a class can require names where they matter and waive them
where they do not.

## Exemptions

- **Declarations with no parameters.** Nothing to name, so nothing to keep stable.
- **Private methods.** They are nobody's contract, so no rename can break an outside caller.
- **`@internal` symbols.** Nothing outside reaches them, so their parameter names are nobody's contract either way.
- **Anonymous classes.** They have no name to import, and no caller beyond the expression building them.
