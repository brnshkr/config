# @brnshkr/config

When a change affects `docs/` or the AI files (`AGENTS.md`, `conf/ai/`), update them in the same change.

## Code

- Many native functions are banned in favor of wrappers (`Str`, `Json`, Symfony `s()`, Filesystem, Finder, Process).
  See `conf/ai/docs/php.md`.
- Sample code calling `@no-named-arguments` classes, the builders and rule classes, passes positional arguments.
- `@example` code is linted. When a rule misfires there,
  disable it in the jsdoc-examples overrides in `src/js/eslint/configs/overrides.ts` or `conf/eslint.ts`,
  never by rewriting the example.
- An exemption for config's own docs or fixtures goes in `conf/eslint.ts`, never in the shipped config.
- A custom rule's docs page carries its examples. Follow it rather than restating it.
- PHP code blocks in docblocks and `.md` pages are checked, formatted and refactored:
  no open tag or strict-types declaration, every name imported, deliberate mistakes under `<!-- brnshkr-skip -->`.
- `RuleDocsTest` pairs every custom rule with its docs page and its counterpart on the other stack.

## Commits

Scopes run area, tool, then rule group: `rule` covers both stacks' custom rules,
`php/phpstan/rule` or `js/eslint/rule` one stack's,
`js/eslint/<module>` a rule group (an override in `overrides.ts` is `js/eslint/jsdoc`),
plus `make`, `ai/mate` and `vscode`.

## Dependencies

- After an install, dev dependencies pin the exact version from the lock file, without a `v`.
  Runtime dependencies (`require`, `dependencies`, `peerDependencies`) keep caret ranges.
- A bumped tool raises its floor in the same change:
  the `peerDependencies` caret and the composer `conflict` range (`<new || >=next-major`).
- The version comes from the `release/*` branch, never from a manifest. `make pack` stamps `VERSION` for its run.
