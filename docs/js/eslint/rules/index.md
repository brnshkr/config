# 🧩 Custom ESLint Rules

All rules ship in the `brnshkr` plugin and are enabled by the default configuration. **Import hygiene**, **naming**, **encapsulation** and **documentation** rules apply everywhere; **public-API** rules only run on the source files a package re-exports through its `package.json#exports`, apart from the check for a docblock declaring two visibilities at once, which applies everywhere.

## Import Hygiene

- [`brnshkr/require-import-alias`](./require-import-alias.md) — imports that resolve into a TypeScript `paths` alias must use the alias form with the fewest path segments
- [`brnshkr/require-import-attributes`](./require-import-attributes.md) — imports of non-JavaScript files must declare a matching `with { type: '...' }` attribute

## Naming

- [`brnshkr/boolish-prefix`](./boolish-prefix.md) — boolean symbols must carry a boolish name prefix, and non-boolean symbols must avoid one
- [`brnshkr/interface-suffix`](./interface-suffix.md) — a class implementing a single `*Interface` must end with the matching prefix

## Encapsulation

- [`brnshkr/internal-usage`](./internal-usage.md) — an `@internal` symbol must not be used from outside the namespace it is internal to

## Documentation

- [`brnshkr/resolvable-doc-reference`](./resolvable-doc-reference.md) — every `@see` and `@link` target must name a symbol that exists

## Public API

- [`brnshkr/api-or-internal-tag`](./api-or-internal-tag.md) — every exported declaration in a public-API source file must carry an `@api` or `@internal` tag
- [`brnshkr/public-api-documentation`](./public-api-documentation.md) — `@api` symbols in a public-API source file must be documented to a consistent JSDoc standard
