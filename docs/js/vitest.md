# Vitest [🔍](../../src/js/vitest/index.ts 'Go to source')

`@brnshkr/config/vitest` is the @brnshkr test configuration, ready to export from a config file.

## Usage

```js
// ./conf/vitest.mjs
export { default } from '@brnshkr/config/vitest';
```

It collects the project's own tests and the spelling test this package ships, so a project writes none for it.
Modules for the optional packages — e.g. `environment`, `ui`
— switch themselves on once the packages they need are installed, so a project configures nothing to gain one.
Imports resolve through the `paths` of `tsconfig.json`.
Set `spelling` to `false` to collect the project's own tests only.

## Customizing

`getConfig()` takes the module toggles, merged with the global Vitest fields, and any further
config entries after them.

```js
// ./conf/vitest.mjs
import { getConfig } from '@brnshkr/config/vitest';

export default getConfig({
  spelling: false,
  test: {
    testTimeout: 120_000,
  },
}, {
  test: {
    setupFiles: [
      './tests/setup.ts',
    ],
  },
});
```

Set a module to `false` to keep it off even when its packages are there.
Entries merge through Vitest's own `mergeConfig`, so a nested field keeps the keys the call does not name.
