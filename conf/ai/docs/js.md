# JS tooling — agent notes

- ESLint rule groups are one file per concern in `src/js/eslint/configs/`.
  A group for an optional plugin turns on only when the plugin is installed.
- A custom rule follows the `add-custom-rule` skill.
- A function declares `@throws` for what it and its callees throw, and `@rejects` for what its promise rejects with,
  except in test files. A `@types` package's `TypeError` counts only in JavaScript, for an untyped or `unknown` argument.
- `src/js/eslint/configs/overrides.ts` ships to consumers; exceptions for this repository go in `conf/eslint.ts`.
- Run `make typegen` after adding or upgrading an ESLint or markdownlint plugin.
- Vitest caches the ESLint config. After editing `src/js/eslint/configs/`, update `tests/js/eslint.test.ts` on its own
  and check that its snapshot changed.
- An update run reports "No test suite found" for the ESLint fixtures; ignore it.
