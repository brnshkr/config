# TypeScript [🔍](../../conf/tsconfig.json 'Go to source')

The TypeScript module is a shareable `tsconfig.json` base, copied into a project and extended through `extends`. Unlike the ESLint and Stylelint modules it is not a builder — it is a plain config file, so customizing means overriding fields in your own `tsconfig.json`.

## What the base config sets

The base leans on a strict, bundler-first, ESNext setup:

- **Strict type checking** beyond `strict` — `noUncheckedIndexedAccess`, `noImplicitOverride`, `noImplicitReturns`, `noPropertyAccessFromIndexSignature`, `noUnusedLocals`/`noUnusedParameters`, `noFallthroughCasesInSwitch`, and friends
- **Modern modules** — `module: "preserve"` with `moduleResolution: "bundler"`, `verbatimModuleSyntax`, and `allowImportingTsExtensions`
- **No emit** — type-checking only (`noEmit`); bundling is left to your build tool
- **JavaScript checked too** — `allowJs` and `checkJs` are on
- A broad `exclude` list so caches, build output, vendored code, and snapshots stay out of the program.

## Extending

Copy the example and point `extends` at the shipped config, then layer your project-specific paths and options on top:

```jsonc
// ./tsconfig.json
{
  "extends": "./node_modules/@brnshkr/config/conf/tsconfig.json",
  "compilerOptions": {
    "paths": {
      "$user/*": ["./src/user/*"],
      "$email/*": ["./src/email/*"]
    }
  }
}
```

The `paths` you define here are also what [`brnshkr/require-import-alias`](./eslint/rules/require-import-alias.md) reads to enforce alias imports.
