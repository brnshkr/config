# `brnshkr/api-or-internal-tag` [🔍](../../../../src/js/eslint/configs/builtin/api-or-internal-tag.ts 'Go to source')

JavaScript mirror of the PHP [`ApiOrInternalTagRule`](../../../php/phpstan/rules/ApiOrInternalTagRule.md) — see there for the rationale. Every exported declaration in a public-API source file must declare its intended visibility by carrying either an `@api` or an `@internal` JSDoc tag.

The rule only runs on **public-API source files** — the `src` files that the package's `package.json#exports` ultimately resolve to. Anything that is not reachable through `exports` is left alone.

```js
// ❌ Bad — exported, but no visibility tag
export class UserService {}

// ✅ Good — '@api' marks the symbol as part of the public surface
/**
 * @api
 */
export class UserService {}

// ✅ Good — '@internal' marks the symbol as not for outside consumption
/**
 * @internal
 */
export class UserService {}
```

## `@file`-level visibility

A `@file` block at the top of the module carrying `@api` or `@internal` sets the effective visibility for every symbol below it, so files that are uniformly public or uniformly internal do not need a tag on each declaration.

```js
/**
 * @file Public entry point for the user module.
 *
 * @api
 */

// ✅ Good — covered by the '@file' '@api' tag above
export class UserService {}
```

## Diagnostics

- `missingTag` — an exported declaration in a public-API source file carries neither `@api` nor `@internal`, and no `@file`-level visibility covers it

## Options

The rule shares its options with [`brnshkr/public-api-documentation`](./public-api-documentation.md); both resolve the public-API file set the same way.

- `packageJsonPath` — path to the `package.json` whose `exports` define the public surface. Defaults to the nearest `package.json` found from the current working directory
- `distRoot` — built output root that `exports` point into (e.g. `dist`). Used to map a published entry back to its source file
- `srcRoot` — source root the built files originate from (e.g. `src`)
- `srcExtensions` — source file extensions to consider when mapping a built entry back to source
