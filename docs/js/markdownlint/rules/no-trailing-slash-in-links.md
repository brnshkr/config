# 📝 `brnshkr/no-trailing-slash-in-links` [🔍](../../../../src/js/markdownlint/configs/builtin/no-trailing-slash-in-links.ts 'Go to source')

A link target never ends in a slash: inline links, images, autolinks, bare URLs, and reference definitions alike.
The fix drops the slash. A target that is only `/` is left alone.

```md
<!-- ❌ Bad — the target ends in a slash -->
See the [docs](./docs/) and <https://example.com/guide/>.

<!-- ✅ Good — the same targets without it -->
See the [docs](./docs) and <https://example.com/guide>.
```
