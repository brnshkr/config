# `brnshkr/no-warning-comments` [🔍](../../../../src/js/markdownlint/configs/builtin/no-warning-comments.ts 'Go to source')

Markdown mirror of ESLint's `no-warning-comments`. Open work belongs in a backlog,
so a `TODO`, `FIXME`, `XXX` or `HACK` fails the page wherever it stands: in prose, in an HTML comment
and in a code block. Inline code is left alone, so a page can still name the term.

```md
<!-- ❌ Bad — open work hidden where no reader sees it -->
<!-- TODO: document the cache flags -->

<!-- ✅ Good — the page says what holds today, and the open work lives in the backlog -->
```
