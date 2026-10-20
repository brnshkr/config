# `brnshkr/fenced-code` [🔍](../../../../src/js/stylelint/configs/builtin/fenced-code.ts 'Go to source')

Stylesheet code blocks in Markdown pages and in the docblocks of scripts and PHP files are linted with the project's
Stylelint config, each as a file of its dialect, so a `scss` block gets the SCSS rules.
A finding names the rule the block broke, on the page's line, and `--fix` writes back into the block.

````md
<!-- ❌ Bad — `colr` is no property -->
```css
.acme-card {
  colr: red;
}
```

<!-- ✅ Good -->
```css
.acme-card {
  color: red;
}
```
````

A block is linted when Stylelint lints files of its language, e.g. `scss` with the `scss` module.
A block preceded by `<!-- brnshkr-skip -->` is left alone.
The rule comes with the `markdown` module, on once `@eslint/markdown` is installed.

Each block is linted as `<file>/<n>.fenced.<lang>`, and the override for `**/*.fenced.*` turns off
what an excerpt cannot satisfy on its own: rules that need the rest of the stylesheet,
e.g. `no-unknown-custom-properties`, and `defensive-css/require-at-layer`.
A block may use a token its page does not define:

```css
.acme-card {
  padding: var(--acme-gap, 1rem);
}
```
