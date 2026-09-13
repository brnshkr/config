# Stylelint [🔍](../../src/js/stylelint/index.ts 'Go to source')

`@brnshkr/config/stylelint` is the @brnshkr stylesheet configuration, ready to export from a config file.

## Usage

```js
// ./conf/stylelint.mjs
export { default } from '@brnshkr/config/stylelint';
```

Modules for dialects and plugins — e.g. `scss`, `modules`, `order`
— switch themselves on once the packages they need are installed, so a project configures nothing to gain one.

## Customizing

`getConfig()` takes the module toggles, merged with the global Stylelint fields, and any further
config entries after them.

```js
// ./conf/stylelint.mjs
import { getConfig } from '@brnshkr/config/stylelint';

export default getConfig({
  scss: false,
  ignoreFiles: [
    'dist/**',
  ],
}, {
  rules: {
    'declaration-no-important': null,
  },
});
```

Set a module to `false` to keep it off even when its packages are there.

Physical properties, units and keywords are fixed to their logical forms for left-to-right, top-to-bottom text.
A right-to-left project passes its own direction:

```js
// ./conf/stylelint.mjs
import { getConfig } from '@brnshkr/config/stylelint';

export default getConfig({
  languageOptions: {
    directionality: {
      block: 'top-to-bottom',
      inline: 'right-to-left',
    },
  },
});
```
