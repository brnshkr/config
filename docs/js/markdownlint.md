# markdownlint [🔍](../../src/js/markdownlint/index.ts 'Go to source')

`@brnshkr/config/markdownlint` is the @brnshkr Markdown configuration, ready to export from a config file.
It is the shape `markdownlint-cli2` reads, so a project needs a config file of its own
rather than extending a shared rule set.

## Usage

```js
// ./conf/markdownlint.mjs
export { default } from '@brnshkr/config/markdownlint';
```

Modules for the optional rule groups — e.g. `links`, `tables`
— switch themselves on once the packages they need are installed, so a project configures nothing to gain one.

## Customizing

`getConfig()` takes the module toggles, merged with the global `markdownlint-cli2` fields, and any further
config entries after them.

```js
// ./conf/markdownlint.mjs
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

Set a module to `false` to keep it off even when its packages are there.
An override needs no `combine` — one written without it merges, rather than being dropped unreported.
