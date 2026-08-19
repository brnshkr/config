# `brnshkr/public-api-documentation` [🔍](../../../../src/js/eslint/configs/builtin/public-api-documentation.ts 'Go to source')

JavaScript mirror of the PHP [`PublicApiDocumentationRule`](../../../php/phpstan/rules/PublicApiDocumentationRule.md), which is the source of truth for the documentation standard and its exemptions. `@api` symbols must be documented to a consistent JSDoc standard.

Like [`brnshkr/api-or-internal-tag`](./api-or-internal-tag.md), the rule only runs on **public-API source files** — the `src` files that `package.json#exports` resolve to — and it additionally requires a top-of-file `@file` block with a description.

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

TypeScript users move the types from the JSDoc tags onto the signature; the rest applies unchanged.


## Options

Identical to [`brnshkr/api-or-internal-tag`](./api-or-internal-tag.md#options) — `packageJsonPath`, `distRoot`, `srcRoot`, and `srcExtensions` all resolve the public-API file set the same way.
