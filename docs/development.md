# 💻 Development

Setup and day-to-day commands for working **on** @brnshkr/config itself.
If you only consume the package, you want the [README](../README.md) and the per-tool docs instead.

## ☕ JS

### Node version

The required Node version is pinned in [`.nvmrc`](../.nvmrc). We use [nvm](https://github.com/nvm-sh/nvm);
with it installed and activated, running `nvm install` from the repo root installs and selects the pinned version.

### Setup

Install dependencies and set up git hooks:

```sh
bun install \
  && bun install-hooks
```

### Scripts

We recommend the scripts in [`package.json`](../package.json) as the primary way to run common tasks
— have a look there for the full list. Frequently used:

- `bun lint` — run ESLint, Stylelint and Commitlint
- `bun inspect:eslint` — inspect the ESLint configuration
- `bun check` — run TypeScript checks, linters and Vitest
- `bun run test` — run the Vitest test suite
- `bun test-update` — run the Vitest test suite and update snapshots
- `bun run build` — build the project and generate types
- `bun watch` — build the project in watch mode

## 🐘 PHP

### PHP version

The required PHP version and build configuration are pinned in [`mise.toml`](../mise.toml).
We use [mise](https://github.com/jdx/mise) with the [verzly/mise-php](https://github.com/verzly/mise-php) plugin;
with it installed and activated, running `mise install` from the repo root installs and selects the pinned version.

### Setup

Install dependencies and set up project tooling:

```sh
composer install \
  && cp -v ./conf/php-cs-fixer.php.example ./conf/php-cs-fixer.php \
  && cp -v ./conf/rector.php.example ./conf/rector.php \
  && cp -v ./conf/phpstan.php.example ./conf/phpstan.php \
  && cp -v ./conf/twig-cs-fixer.php.example ./conf/twig-cs-fixer.php
```

### Make

We recommend [GNU Make](https://www.gnu.org/software/make) as the primary task runner.
Run `make help` (or just `make`) to list every target;
see the [Makefile docs](./php/Makefile.md) for the foundation it builds on.
If you need local overrides, create a `./.local/Makefile`
— the main Makefile includes it automatically when present. Frequently used:

- `make help` — show available targets and usage
- `make rector` — apply automated PHP refactorings
- `make php-cs-fixer` — format and fix coding-style issues
- `make phpstan` — run static analysis
- `make test` — run the PHPUnit test suite
- `make test-update` — run the PHPUnit test suite and update snapshots
- `make check` — run Rector, PHP-CS-Fixer, Twig-CS-Fixer, PHPStan and PHPUnit

## 🤖 AI tooling

This repo ships a project-aware MCP server ([Symfony AI Mate](https://github.com/symfony/ai-mate)) for AI assistants,
wired up under [`conf/ai/`](../conf/ai). It installs itself on `composer install` (the `mate/` directory is generated),
and is picked up automatically by Claude Code (`.mcp.json`) and Codex (`./conf/ai/bin/codex`).
Agents must read [`AGENTS.md`](../AGENTS.md) first;
how the setup works and how to extend it lives in [`conf/ai/docs/ai.md`](../conf/ai/docs/ai.md).
