# ESLint [🔍](../../../src/js/eslint/index.ts 'Go to source')

The ESLint module is a flat-config builder consumed from `@brnshkr/config/eslint`. `getConfig()` returns a [`FlatConfigComposer`](https://github.com/antfu/eslint-flat-config-utils) that wires up the @brnshkr opinions for JavaScript, TypeScript, JSON, Markdown, YAML, TOML, Svelte, and more — activating each plugin lazily, only when its optional peer dependency is installed. The composer resolves to the final flat-config array and also exposes `.append()`, `.prepend()`, and similar helpers for downstream composition.

## Custom Rules

The default configuration ships a small `brnshkr` plugin, enabled out of the box. See [Custom ESLint Rules](./rules/index.md).

## Customizing

`getConfig()` takes the per-module toggles (merged with global flat-config fields) as its first argument and any additional flat configs as the rest:

```js
// ./eslint.config.mjs

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
