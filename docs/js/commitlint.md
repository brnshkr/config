# commitlint [🔍](../../src/js/commitlint/index.ts 'Go to source')

The commitlint module is a config builder consumed from `@brnshkr/config/commitlint`.
`getConfig()` returns a final commitlint `UserConfig` that wires up the @brnshkr opinions for header, scope,
subject and body shape — activating each preset lazily, only when its optional peer dependency is installed.
An always-on rule baseline is layered in unconditionally.

The check belongs on the `commit-msg` hook, where a bad message is rejected while it is still cheap to rewrite.

## Customizing

`getConfig()` takes the per-module toggles (merged with global commitlint fields) as its first argument
and any additional config entries as the rest:

```js
// ./conf/commitlint.config.mjs

import { getConfig } from '@brnshkr/config/commitlint';

export default getConfig({
  conventional: false,
}, {
  rules: {
    'scope-min-length': [2, 'always', 3],
  },
});
```
