---
name: brnshkr-module-config
description: Read the resolved config of the @brnshkr/config PHP tools (PHPStan, Rector, PHP-CS-Fixer, Twig-CS-Fixer) through Mate, e.g. whether a rule is already on, when the project's config file only calls the builder. Not for running the tools (`mate-brnshkr-code-quality`).
---

# Module configs

- `brnshkr-module-list`:
  each module with the config file make runs it with, and its packages, required or optional, installed or not.
- `brnshkr-module-config` (req `module`; opt `section`): the module's resolved config, or one part of it.

## Workflow

1. Read the config only for what the project's config file does not spell out, e.g. whether a rule is already on:
   `make mate -- tools:call brnshkr-module-config --module=phpstan`.
   Turning a rule on or off needs no read.
2. Read one part with a dotted path: `--section=parameters` or `--section=services.6`.
3. A part missing from the config: `make mate -- tools:call brnshkr-module-list` shows whether its package is installed.

## Reading

- A part too long to print reads
  ``Omitted <N> characters. Run `brnshkr-module-config --module=… --section=…`.``. Run that call.
- `brnshkr-module-config` rejects an unknown `module` or `section` and lists the valid ones.
