# 🧩 Custom ESLint Rules

All rules ship in the `brnshkr` plugin and are enabled by the default configuration. They come in two groups: **import hygiene** rules that apply everywhere, and **public-API** rules that only run on the source files a package re-exports through its `package.json#exports`.

## Import Hygiene

- [`brnshkr/require-import-alias`](./require-import-alias.md) — relative imports that resolve into a TypeScript `paths` alias must use the alias form
- [`brnshkr/require-import-attributes`](./require-import-attributes.md) — imports of non-JavaScript files must declare a matching `with { type: '...' }` attribute

## Public API

These two are the JavaScript mirrors of the PHP [`ApiOrInternalTagRule`](../../../php/phpstan/rules/ApiOrInternalTagRule.md) and [`PublicApiDocumentationRule`](../../../php/phpstan/rules/PublicApiDocumentationRule.md), and share the same options.

- [`brnshkr/api-or-internal-tag`](./api-or-internal-tag.md) — every exported declaration in a public-API source file must carry an `@api` or `@internal` tag
- [`brnshkr/public-api-documentation`](./public-api-documentation.md) — `@api` symbols in a public-API source file must be documented to a consistent JSDoc standard
