# Stylelint [🔍](../../src/js/stylelint/index.ts 'Go to source')

The Stylelint module is a config builder consumed from `@brnshkr/config/stylelint`. `getConfig()` returns a final Stylelint `Config` that wires up the @brnshkr opinions for plain CSS, SCSS, CSS Modules, HTML-embedded styles, Recess-style ordering, design-token enforcement, and more — activating each plugin lazily, only when its optional peer dependency is installed. An always-on CSS baseline (general rule hardening and shared ignores) is layered in unconditionally.

## Customizing

`getConfig()` takes the per-module toggles (merged with global Stylelint fields) as its first argument and any additional config entries as the rest:

```js
// ./stylelint.config.mjs

import { getConfig } from '@brnshkr/config/stylelint';

export default getConfig({
  scss: false,
  ignoreFiles: [
    'dist/**',
  ],
}, {
  rules: {
    'color-no-hex': null,
  },
});
```
