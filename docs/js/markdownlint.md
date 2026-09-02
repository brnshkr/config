# markdownlint [🔍](../../src/js/markdownlint/index.ts 'Go to source')

The markdownlint module is a config builder consumed from `@brnshkr/config/markdownlint`.
`getConfig()` returns a final `markdownlint-cli2` config carrying the @brnshkr opinions for line length,
emphasis, table style, duplicate headings, inline HTML, and more — activating each plugin lazily,
only when its optional peer dependency is installed.
An always-on baseline (general rule hardening and shared ignores) is layered in unconditionally.

## Customizing

`getConfig()` takes the per-module toggles (merged with global `markdownlint-cli2` fields) as its first argument and
any additional config entries as the rest:

```js
// ./conf/markdownlint.config.mjs

import { getConfig } from '@brnshkr/config/markdownlint';

export default getConfig({
  links: false,
  ignores: [
    'CHANGELOG.md',
  ],
}, {
  overrides: [
    {
      filter: [
        'docs/generated/**',
      ],
      config: {
        'first-line-heading': false,
      },
    },
  ],
});
```

An override written without `combine` is given `merge` since `markdownlint-cli2` drops one
whose value is neither `merge` nor `replace`, without reporting it.
