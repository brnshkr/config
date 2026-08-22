# JS tooling — agent notes

Agent knowledge beyond `docs/js/`.

## Architecture

- ESLint flat-config builder: `src/js/eslint/index.ts` composes the rule groups from `src/js/eslint/configs/` (one file per concern: javascript, typescript, style, jsdoc, import, node, json, markdown, svelte, toml, test, overrides, ...). Optional plugins load lazily via `src/js/eslint/utils/module.ts` — only installed optional peer deps activate their group.
- Custom ESLint rules live in `src/js/eslint/configs/builtin/` (kebab-case file per rule); user docs per rule in `docs/js/eslint/rules/`. New rule = implementation + doc page + snapshot update. Verify pairing and cross-stack parity with MCP `project-rule-docs-audit`.
- Stylelint builder: `src/js/stylelint/index.ts` with named modes in `src/js/stylelint/configs/` (baseline, defensive, logical, scss, strict, ...).
- Rule-group overrides for special globs sit in `src/js/eslint/configs/overrides.ts` — e.g. the `GLOB_EXAMPLES` block relaxing rules inside JSDoc `@example` code.
- That file ships to consumers; exceptions for this repo's own sources belong in `conf/eslint.config.ts`.
- Build: tsdown bundles `src/js/` to `dist/` per `conf/tsdown.config.ts`; entry points in `package.json#exports`. The scripts chunk rewrites `eslint.config.ts` → `eslint.config.mjs` via a renderChunk plugin.

## Lint infra

- `make clean` removes `.cache` wholesale, the ESLint cache included, so the next lint runs cold. Remove the one cache you mean to remove.
- This repo lints itself through scripts named after the tools: `bun eslint`/`bun stylelint` → `scripts/{eslint,stylelint}.ts` (apply `conf/*.config.ts`), `bun typescript` → `tsc --noEmit`. The script name wins over the same-named binary; to hit the raw binary use `bun --bun x eslint --config conf/eslint.config.ts`.

## Typegen

- `bun typegen` regenerates `src/js/eslint/types/declarations/typegen.d.ts` (gitignored build artifact) by introspecting installed plugins. Run after adding/upgrading ESLint or Stylelint plugins; `bun build` runs it implicitly.

## Tests + snapshots

- The ESLint config build is CACHED inside Vitest — the first `-u` run may not pick up edits to `src/js/eslint/configs/*`; re-run `bun --bun vitest --run -u tests/js/eslint.test.ts` explicitly and `git diff` the snapshot to confirm it actually updated.
- `-u` globs the ESLint fixture files (`tests/js/fixtures/eslint/*`) as test suites and prints bogus "No test suite found" failures — ignore them; the real config snapshot still updates.
- MCP `project-tests-run` with `suite=js` wraps `bun run test` / `test-update` and reports changed snapshots under `tests/js/__snapshots__`.
