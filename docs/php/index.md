# 🐘 PHP

The PHP half of **@brnshkr/config**: fluent config builders for every tool,
a shared file-discovery helper they all delegate to, a Composer plugin that bootstraps downstream projects,
and a reusable Makefile foundation. Per-tool pages below; the [README](../../README.md#-php) covers setup.

## Tooling

- [PHPStan](./phpstan/index.md) — fluent config builder + the custom standalone and architecture rules
- [PHP-CS-Fixer](./PhpCsFixer.md) — @brnshkr coding-style config builder
- [Rector](./Rector.md) — @brnshkr refactoring config builder
- [Twig-CS-Fixer](./TwigCsFixer.md) — @brnshkr template-style config builder

## Shared & workflow

- [FileFinder](./FileFinder.md) — the file-discovery helper every tool config delegates to
- [EditorUrl](./EditorUrl.md) — editor-jump URL template builder consumed by PHPStan and Rector
- [Composer Plugin](./Composer.md) — `setup` and the other `brnshkr:config:*` helper commands
- [Makefile](./Makefile.md) — reusable task-runner foundation
