# ESLint [🔍](../../../src/js/eslint/index.ts 'Go to source')

`@brnshkr/config/eslint` is the @brnshkr lint configuration, ready to export from a flat-config file.

## Usage

```js
// ./conf/eslint.mjs
export { default } from '@brnshkr/config/eslint';
```

Modules for languages and plugins — e.g. `typescript`, `svelte`, `yaml`
— switch themselves on once the packages they need are installed, so a project configures nothing to gain one.

## Customizing

`getConfig()` takes the module toggles, merged with the global flat-config fields, and any further
flat configs after them.

```js
// ./conf/eslint.mjs
import { getConfig } from '@brnshkr/config/eslint';

export default getConfig({
  svelte: false,
  files: [
    'src/**',
  ],
}, {
  files: [
    'scripts/**',
  ],
  rules: {
    'no-console': 'off',
  },
});
```

Set a module to `false` to keep it off even when its packages are there.
What comes back is a [`FlatConfigComposer`](https://github.com/antfu/eslint-flat-config-utils),
so `.append()`, `.prepend()` and `.override()` are available where a config is assembled in steps.

## Rules

- [Custom rules](./rules/index.md) — what the `brnshkr` plugin checks beyond the plugins above
