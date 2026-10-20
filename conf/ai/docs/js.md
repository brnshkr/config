# JS tooling — agent notes

- ESLint rule groups are one file per concern in `src/js/eslint/configs/`.
  A group for an optional plugin turns on only when the plugin is installed.
- A custom ESLint rule is a kebab-case file in `src/js/eslint/configs/builtin/`, a page in `docs/js/eslint/rules/`
  and a snapshot update. Check the pairing with `project-rule-docs-audit`.
- A custom markdownlint rule mirrors an ESLint one: a kebab-case file in `src/js/markdownlint/configs/builtin/`,
  listed in that directory's `RULE_DEFINITIONS` and handed to `customRules` as an object, plus a page in
  `docs/js/markdownlint/rules/`.
- A custom Stylelint rule is a `stylelint.createPlugin()` file in `src/js/stylelint/configs/builtin/`,
  registered in `plugins` by the config of the module it needs, plus a page in `docs/js/stylelint/rules/`.
- A function declares `@throws` for what it and its callees throw, and `@rejects` for what its promise rejects with,
  except in test files. A `@types` package's `TypeError` counts only in JavaScript, for an untyped or `unknown` argument.
- `src/js/eslint/configs/overrides.ts` ships to consumers; exceptions for this repository go in `conf/eslint.ts`.
- Run `make typegen` after adding or upgrading an ESLint or markdownlint plugin.
- Vitest caches the ESLint config. After editing `src/js/eslint/configs/`, update `tests/js/eslint.test.ts` on its own
  and check that its snapshot changed.
- An update run reports "No test suite found" for the ESLint fixtures; ignore it.
