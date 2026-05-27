# `brnshkr/public-api-documentation` [🔍](../../../../src/js/eslint/configs/builtin/public-api-documentation.ts 'Go to source')

JavaScript mirror of the PHP [`PublicApiDocumentationRule`](../../../php/phpstan/rules/PublicApiDocumentationRule.md) — see there for the rationale. Symbols marked as `@api` in a public-API source file must be documented to a consistent JSDoc standard.

Like [`brnshkr/api-or-internal-tag`](./api-or-internal-tag.md), the rule only runs on **public-API source files** — the `src` files that `package.json#exports` resolve to.

For each public-API file the rule requires:

- a top-of-file `@file` JSDoc block with a description
- a description before the first JSDoc tag on every `@api` symbol
- a `@param` line with prose for every parameter
- a `@returns` line with prose whenever the symbol returns something non-void
- an `@example` block whenever calling it involves arguments

```js
// ❌ Bad — '@api' export with no description and an undocumented parameter
/**
 * @api
 */
export class UserService {
  rename(user, name) {
    // ...
  }
}

// ✅ Good
/**
 * Coordinates user-account mutations.
 *
 * @api
 */
export class UserService {
  /**
   * Renames the given user.
   *
   * @param {User} user the user being renamed
   * @param {string} name new display name, trimmed and validated against the username policy
   *
   * @returns {User} the mutated user, freshly refetched from the database
   *
   * @example
   * userService.rename(user, 'Ada Lovelace');
   */
  rename(user, name) {
    // ...
  }
}
```

The same standard applies to exported functions:

```js
// ❌ Bad — '@api' function with no description and an undocumented parameter
/**
 * @api
 */
export const formatUser = (user, isShort) => {
  // ...
};

// ✅ Good
/**
 * Formats a user for display in a listing.
 *
 * @api
 *
 * @param {User} user the user being rendered
 * @param {boolean} isShort whether to collapse the result to a single line
 *
 * @returns {string} the rendered display string
 *
 * @example
 * formatUser(user, false);
 */
export const formatUser = (user, isShort) => {
  // ...
};
```

TypeScript users move the types from the JSDoc tags onto the signature; the rest of the rule applies unchanged.

## Exemptions

A few exemptions keep the rule pragmatic: methods tagged `@internal` are skipped, constructors do not need their own description (the `@file` and class blocks already cover the type's purpose), fluent methods returning `this`/the class type skip the `@returns`/`@example` checks, and interface or abstract methods skip `@example` since they have no implementation to demonstrate.

## Diagnostics

- `missingFileDescription` — public-API source file lacks a top-of-file `@file` JSDoc block with a description
- `missingDescription` — an `@api` symbol carries tags but no prose description before the first one
- `missingParam` — an `@api` symbol has parameters not covered by a `@param` line with prose
- `missingReturns` — an `@api` symbol returns a non-void type but has no `@returns` line with prose
- `missingExample` — an `@api` symbol accepts arguments but has no `@example` block

## Options

Identical to [`brnshkr/api-or-internal-tag`](./api-or-internal-tag.md#options) — `packageJsonPath`, `distRoot`, `srcRoot`, and `srcExtensions` all resolve the public-API file set the same way.
