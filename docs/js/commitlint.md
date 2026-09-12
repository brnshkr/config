# commitlint [🔍](../../src/js/commitlint/index.ts 'Go to source')

`@brnshkr/config/commitlint` is the @brnshkr commit-message configuration, ready to export from a config file.
It belongs on the `commit-msg` hook, where a bad message is rejected while it is still cheap to rewrite.

## Usage

```js
// ./conf/commitlint.mjs
export { default } from '@brnshkr/config/commitlint';
```

Modules for the presets — e.g. `conventional`
— switch themselves on once the packages they need are installed, so a project configures nothing to gain one.

## Customizing

`getConfig()` takes the module toggles, merged with the global commitlint fields, and any further
config entries after them.

```js
// ./conf/commitlint.mjs
import { getConfig } from '@brnshkr/config/commitlint';

export default getConfig({
  conventional: false,
}, {
  rules: {
    'scope-min-length': [2, 'always', 3],
  },
});
```

Set a module to `false` to keep it off even when its packages are there.
