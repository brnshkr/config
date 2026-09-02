# `brnshkr/boolish-prefix` [🔍](../../../../src/js/eslint/configs/builtin/boolish-prefix.ts 'Go to source')

JavaScript mirror of the PHP [`BoolishPrefixRule`](../../../php/phpstan/rules/BoolishPrefixRule.md),
which is the source of truth for the prefix families, the reverse check, the exemptions, and the whole-word rule.
Boolean names must carry a boolish prefix, non-boolean names must not.

Nothing about the rule is tied to classes — a plain binding or an arrow function is judged like a property or a method.

```js
// ❌ Bad
const active = true;
const verified = () => active;

// ✅ Good
const isActive = true;
const isVerified = () => isActive;
```

## Type information

With a type-aware configuration the rule asks the TypeScript checker, so it follows aliases and inferred returns. An
`async` function is judged on what it resolves to — `isVerified(): Promise<boolean>` reads as the boolean it delivers —
and a getter is judged as the value it exposes rather than as a method.

Without type information — plain JavaScript, or files outside the type-aware globs — the rule reads what the source
spells out literally: a type annotation, an initializer, or a returned boolean literal or comparison. Anything indirect
stays unknown, so the fallback reports less rather than guessing.

```js
// ✅ classified from the literal
const isActive = true;

// ❌ classified from the literal
const active = true;

// untouched — the value is unknown without types
const resolvedFlag = loadUser();
```

Object literal keys are never checked in either mode; like PHP array shapes, they routinely mirror a foreign schema the
project does not name.

## Skipped symbols

Where the PHP rule skips members inherited from `/vendor/`, this one skips class members that override or implement a
declaration from an external package (`node_modules`), plus constructors and computed names. `any`, a generic, and an
untyped parameter are the unresolvable types here.
