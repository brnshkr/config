# 🧩 Custom PHPStan Rules

The custom PHPStan rules ship in two flavors. **Standalone rules** are general-purpose checks enabled by the default configuration. **Architecture presets** are opinionated bundles of class-placement and isolation rules tailored to a specific framework or architecture style — they are opt-in and configured through `setArchitecture()`.

## Naming

- [`BoolishPrefixRule`](./BoolishPrefixRule.md) — boolean symbols must use a boolish prefix
- [`InterfaceSuffixRule`](./InterfaceSuffixRule.md) — single-interface implementers must carry the matching suffix

## Encapsulation

- [`InternalUsageRule`](./InternalUsageRule.md) — `@internal` symbols may only be used from their own declaring namespace or below

## Documentation

- [`ResolvableDocReferenceRule`](./ResolvableDocReferenceRule.md) — every `@see` and `@link` target must name a symbol that exists

## Public API

- [`ApiOrInternalTagRule`](./ApiOrInternalTagRule.md) — every symbol must declare `@api` or `@internal`
- [`NoNamedArgumentsTagRule`](./NoNamedArgumentsTagRule.md) — `@api` symbols must also carry `@no-named-arguments`
- [`PublicApiDocumentationRule`](./PublicApiDocumentationRule.md) — `@api` symbols must be documented in prose

## Architecture Presets

- [Architecture presets](./architecture/index.md) — framework and architecture-style rule bundles configured through `setArchitecture()`
