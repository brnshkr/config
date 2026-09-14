# 💻 Development

Setup and day-to-day commands for working **on** @brnshkr/config itself.
If you only consume the package, you want the [README](../README.md) and the per-tool docs instead.

## Setup

With both version managers below in place, one command installs everything:

```sh
make startup
```

`startup` installs each stack, writes the tool configs this repository does not track,
builds `./dist/` and installs the git hooks.

## ☕ JS

### Node version

The required Node version is pinned in [`.nvmrc`](../.nvmrc). We use [nvm](https://github.com/nvm-sh/nvm);
with it installed and activated, running `nvm install` from the repo root installs and selects the pinned version.

### Targets

Everything runs through `make`; `make help` lists what this checkout can actually run,
and [`./docs/Makefile.md`](./Makefile.md) is the reference.
Frequently used:

- `make fix` — every fixer, writing its fixes
- `make check` — every fixer and analyzer, writing nothing
- `make ci` — `make check` then `make test`
- `make vitest` — the Vitest suite, `make vitest-update` to update its snapshots
- `make build` — regenerate the types and build `./dist/`, `make watch` to rebuild as sources change
- `make inspect-eslint` — inspect the ESLint configuration, `make inspect-eslint-stats` to also time every rule

## 🐘 PHP

### PHP version

The required PHP version and build configuration are pinned in [`mise.toml`](../mise.toml).
We use [mise](https://github.com/jdx/mise) with the [verzly/mise-php](https://github.com/verzly/mise-php) plugin;
with it installed and activated, running `mise install` from the repo root installs and selects the pinned version.

### Targets

The same `make` as above; [`./docs/php/Makefile.md`](./php/Makefile.md) is the PHP reference.
Frequently used:

- `make phpstan` — static analysis, `make phpstan-list` for the files it reads
- `make rector` — automated refactorings, `make rector-dry-run` to preview them
- `make php-cs-fixer` — coding style, `make php-cs-fixer-dry-run` to preview
- `make phpunit` — the PHP tests, `make phpunit-update` to update their snapshots

## 🤖 AI tooling

This repo ships a project-aware MCP server ([Symfony AI Mate](https://github.com/symfony/ai-mate)) for AI assistants,
wired up under [`./conf/ai/`](../conf/ai). It installs itself on `composer install` (the `mate/` directory is generated),
and is picked up automatically by Claude Code (`.mcp.json`) and Codex (`./conf/ai/bin/codex`).
Agents must read [`AGENTS.md`](../AGENTS.md) first;
how the setup works and how to extend it lives in [`./conf/ai/docs/ai.md`](../conf/ai/docs/ai.md).
