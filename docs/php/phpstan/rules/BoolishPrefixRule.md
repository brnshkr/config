# `BoolishPrefixRule` [🔍](../../../../src/php/PhpStan/Rule/BoolishPrefixRule.php 'Go to source')

Every boolean-typed symbol must start with a recognized boolish prefix (`is`, `has`, `can`, and so on),
and — in reverse — a non-boolean symbol must not. The aim is that boolean-ness is obvious from the name alone,
so a name never claims a boolean it isn't nor hides one it is.
The check spans variables, parameters, properties, class and namespaced constants,
and method or function return types; `?bool` and `bool|null` count as boolean.

```php
final class User
{
    // ❌ Bad — boolean property and method without a boolish prefix
    public bool $active;
    public function verified(): bool {}

    // ✅ Good
    public bool $isActive;
    public function isVerified(): bool {}
}
```

The prefix must be a whole leading **word**, not a coincidental substring:
`isReady` and `IS_VALID` qualify, but `island` and `domain` do not.

Recognized prefixes fall into a few families — modal and copula verbs (`is`, `has`, `can`, …),
capability verbs (`needs`, `requires`, `supports`, …),
and object-relation verbs (`contains`, `allows`, `equals`, …)
— each reading as boolean on a value and on a method alike.
Two are one-sided: `as` flags a value-holder only (on a method it reads as a converter),
while `do` marks a command, allowed anywhere but never reserved.
A method named `as`/`to` + `bool`/`boolean` is itself a converter, satisfying the rule by naming `bool` as its target.

```php
final class Report
{
    public bool $hasErrors;

    public function containsKey(string $key): bool {}

    public function toArray(bool $asAssociative): array {} // 'as' flags a value-holder

    public function asBoolean(): bool {}                   // 'as'/'to' + bool/boolean is a converter
}
```

## Reserved prefixes

The reverse keeps names honest: a non-boolean symbol must not start with a reserved boolish prefix.

```php
// ❌ Bad — these read as boolean but are not
final class BadReport
{
    public const string IS_LABEL = 'draft';

    public int $isCount;

    public function hasName(): string {}

    public function asBoolean(): string {}
}

// ✅ Good
final class GoodReport
{
    public const string LABEL = 'draft';

    public int $count;

    public function getName(): string {}
}
```

Two exemptions cover routine, legitimate collisions:

- `do` is never reserved — commands such as `doReset(): void` are expected to return a non-boolean.
- The colliders `matches`, `starts`, and `ends` are reserved on methods and functions only.
  Their third-person form is a canonical non-boolean value,
  so `$matches` (a `preg_match` result), `$startsAt`, and `$endsAt` stay free,
  while `startsWith(): array` is still flagged.

## Exemptions

The rule only governs names the project is free to choose.
A method that overrides or implements a declaration from a vendor (`/vendor/`) parent, interface, or trait is skipped,
as are magic methods other than `__construct`.
A type that cannot be resolved to clearly boolean or clearly non-boolean
— a generic, `mixed`, an untyped parameter, a `bool|int` union — is left alone in both directions.

The JavaScript counterpart is [`brnshkr/boolish-prefix`](../../../js/eslint/rules/boolish-prefix.md).
