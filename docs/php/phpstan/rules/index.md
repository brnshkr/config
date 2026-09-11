# 🧩 Custom PHPStan rules

The custom PHPStan rules ship in two flavors. **Standalone rules** are general-purpose checks enabled by the default
configuration. **Architecture presets** are opinionated bundles of class-placement and isolation rules tailored
to a specific framework or architecture style — they are opt-in and configured through `addArchitecture()`.

## Naming

- [`BoolishPrefixRule`](./BoolishPrefixRule.md)
  — boolean symbols must use a boolish prefix
- [`InterfaceSuffixRule`](./InterfaceSuffixRule.md)
  — single-interface implementers must carry the matching suffix

## Encapsulation

- [`InternalUsageRule`](./InternalUsageRule.md)
  — `@internal` symbols may only be used from their own declaring namespace or below
- [`InternalExposureRule`](./InternalExposureRule.md)
  — an `@api` signature must not name an `@internal` type

## Documentation

- [`ResolvableDocReferenceRule`](./ResolvableDocReferenceRule.md)
  — every `@see` and `@link` target must name a symbol that exists

## Public API

- [`ApiOrInternalTagRule`](./ApiOrInternalTagRule.md) — every symbol must declare `@api` or `@internal`
- [`NamedArgumentsTagRule`](./NamedArgumentsTagRule.md)
  — every symbol exposing parameters must carry `@named-arguments` or `@no-named-arguments`
- [`NamedArgumentsUsageRule`](./NamedArgumentsUsageRule.md)
  — a call to a `@named-arguments` symbol must name every argument
- [`ServiceArgumentBindingRule`](./ServiceArgumentBindingRule.md)
  — a service argument bound by name must name a real constructor parameter
- [`PublicApiDocumentationRule`](./PublicApiDocumentationRule.md) — `@api` symbols must be documented in prose

## Architecture presets

- [Architecture presets](./architecture/index.md)
  — framework and architecture-style rule bundles configured through `addArchitecture()`
