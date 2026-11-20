---
name: mate-brnshkr-code-quality
description: Check and fix code through Mate with the quality tools the @brnshkr/config Makefile runs (ESLint, Stylelint, markdownlint, …), narrowed to the files you touched. For PHPStan and Rector prefer `mate-phpstan-static-analysis` and `mate-rector-refactoring`. Not for tests (`mate-brnshkr-test-run`).
---

# Code quality

Runs one quality tool through its make target and returns `exitCode` with `findings` or `output`:

- `brnshkr-quality-check` (req `tool`; opt `isDryRun`, `isGrouped`, `arguments`):
  a fixer only reports unless `isDryRun` is false. An analyzer never writes.

## Workflow

1. Check what you touched, not the project:
   `make mate -- tools:call brnshkr-quality-check --tool=eslint --arguments='./src/user.ts'`.
   Several paths go in `ARGS`:
   `make mate ARGS='tools:call brnshkr-quality-check --tool=eslint "--arguments=./src/user.ts ./src/email.ts"'`.
   `typescript` checks the whole project and takes no paths.
2. Many findings: count them by rule with `--isGrouped` first, then fix one rule at a time.
3. A fixer's finding carries the diff it would write. Apply it with `--isDryRun=false`, then read the diff it wrote.
4. Re-run the same scope until `exitCode` is `0`. Once, before handing work back, run the tool without `arguments`.

## Reading

- A dry run answers `findings`, each with `file`, `line`, `rule` and `message`.
  A fixer's findings carry `file`, `rule` and `diff` instead, one per file.
  `exitCode` `0` with no `findings` means clean.
- A run that failed without findings answers `output` instead, the tool's own report.
- A grouped run is a count: its `exitCode` is `0` with findings too.
  The last line of `output` says `✘ N findings` or `✔ No findings`.
- `output` is the tool's own report without colors, at most its last 20000 characters.
  A leading `[... N characters truncated ...]` means the scope is too wide.
- `brnshkr-quality-check` rejects an unknown `tool` and lists this project's tools.

## Failure paths

- "No quality tool is installed.": the project has none of the tools the Makefile runs.
- "The "--fix" option cannot be used in a dry run.": apply fixes with `--isDryRun=false`, not the tool's own flag.
- Do not add a suppression comment, an ignore entry or a baseline line to make a run pass. Ask the user.
