# Makefile [🔍](../../conf/Makefile 'Go to source')

The shipped `Makefile` is a reusable foundation for downstream PHP projects.
Include it from your own `Makefile` and get the @brnshkr tool-runner targets, an auto-generated help system,
CLI-style argument forwarding, ANSI theming, and editor-aware hyperlinks for free.

```Makefile
include ./vendor/brnshkr/config/conf/Makefile
```

The full reference lives in the header comment block of [`conf/Makefile`](../../conf/Makefile). `make help` (and `make
help vvv` for the complete view) lists what is currently registered.

## Auto-included Makefiles

When the foundation is included it also auto-imports any `Makefile` or `*.mk` it finds under the following paths:

- `./`
- `./make/`
- `./conf/`
- `./conf/make/`
- `./.local/`
- `./.local/make/`

## Tool-runner targets

Each tool ships a primary target plus a few variants. Every target reads its configuration from `./conf/<tool>.php` by
convention (override with `<TOOL>_CONFIG`) and forwards trailing `ARGS` to the underlying tool.

| Target | Runs | Variants |
| --- | --- | --- |
| `make phpstan` | `phpstan analyze` | `-debug`, `-raw`, `-list` |
| `make php-cs-fixer` | `php-cs-fixer fix` | `-debug` (lists rules via `describe`), `-dry-run`, `-list` |
| `make rector` | `rector process` | `-debug`, `-dry-run`, `-list` |
| `make twig-cs-fixer` | `twig-cs-fixer lint --fix` | `-debug`, `-dry-run` (lint without fix) |

Per-tool overrides follow the `<TOOL>` / `<TOOL>_CONFIG` / `<TOOL>_FLAGS` pattern (`PHP_STAN`, `PHP_STAN_CONFIG`,
`PHP_STAN_FLAGS`, and so on). All defined with `?=`, so downstream Makefiles can override them before including the
foundation.
