# TypeScript [🔍](../../conf/tsconfig.json 'Go to source')

The shipped `tsconfig.json` is the @brnshkr compiler baseline: strict beyond `strict`, bundler-first,
type-checking only. Unlike the other modules it is a plain config file rather than a builder.

## Usage

```jsonc
// ./conf/tsconfig.json
{
  "extends": "@brnshkr/config/conf/tsconfig.json"
}
```

`make configs` writes it and links `./tsconfig.json` to it, so `${configDir}` resolves from the root, where
`tsc` and the editor load the project. Without symlinks the root gets `{ "extends": "./conf/tsconfig.json" }`.

Emitting is left to the build tool, and JavaScript files are checked alongside TypeScript ones.

## Customizing

Override a field in your own `conf/tsconfig.json`; it wins over the base.

```jsonc
// ./conf/tsconfig.json
{
  "extends": "@brnshkr/config/conf/tsconfig.json",
  "compilerOptions": {
    "paths": {
      "$user/*": ["./src/user/*"],
      "$email/*": ["./src/email/*"]
    }
  }
}
```

The `paths` defined here are what [`brnshkr/require-import-alias`](./eslint/rules/require-import-alias.md)
reads to enforce alias imports.
