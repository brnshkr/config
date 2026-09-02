# brnshkr/config — agent rules

Principles: less code is better code.
Simplicity, brevity, maintainability, readability, correctness, flexibility.
Match existing patterns before inventing new ones.

MUST: when a change affects anything covered by `docs/` or by these AI files (this file, `AGENTS.md`, `conf/ai/docs/*`),
update them in the same change. New convention/tool/gotcha → add it. Changed behavior → fix the covering page.
Stale guidance is worse than none.

## Tools

- Prefer `project-*` MCP tools:
  `project-quality-check` (phpstan/php-cs-fixer/rector/twig-cs-fixer/eslint/markdownlint/stylelint/typescript; dry-run default),
  `project-tests-run` (`php`=Pest, `js`=Vitest; filter + snapshot update + changed-snapshot report),
  `project-modules-list`/`project-module-config`,
  `project-version-sync-check`,
  `project-rule-docs-audit`,
  `project-commitlint-check`.
- `phpstan-analyse`/`phpstan-clear-cache`: always pass `configuration=conf/phpstan.php` (detector only finds `phpstan.neon*`).
  `phpunit-run` = Pest via custom_command.
- Deep refs in `conf/ai/docs/`: `ai` (this AI/MCP setup — read before changing it), `commit`, `php`, `js`, `make`.

## Code style

Tooling auto-fixes most style — `project-quality-check` reports the rest.
The custom rules carry examples in `docs/php/phpstan/rules/` + `docs/js/eslint/rules/`;
follow them, don't restate them. Beyond what tooling catches:

### PHP

- Summary line = full sentence (capital + period). `@param`/`@return`/`@throws` descriptions = lowercase fragments,
  no trailing period.
- PHPDoc array shapes multi-line: one key per line, 4-space indent, trailing comma.
  Never `array{a: int, b: string}` on one line.
- Plain `@param`/`@return`/`@var`; reach for `@phpstan-*` only when the type uses `self::*` or sits on a typed
  `const array` (`no_superfluous_phpdoc_tags` strips a plain `@var` there).
- Type hardening (`non-empty-*`, `positive-int`, `key-of<>`, ...) only where the domain demands it;
  generic utilities take arbitrary input.
- Naming that drives generation: boolean symbols need a boolish prefix (`is`/`has`/`can`; `as` for flag params/props),
  single-`*Interface` implementers take the matching suffix, traits suffix `Trait`, `@api` classes also carry `@no-named-arguments`.
- Else-if chains or lookup maps over `switch` (not lint-enforced — apply it).
- Many native functions are banned for wrappers (`Str`, `Json`, `Filesystem`, Symfony `s()`) — see `conf/ai/docs/php.md`.
- Sample code calling `@no-named-arguments` classes (the builders + rule classes) uses positional args only.

### TS

- Casts `<Type>value` / `<Type><unknown>value`, never `as`.
- No TS enums → `const X = <const>{ ... }` + `type X = typeof X[keyof typeof X]`. No `for..in` → `Object.keys/values/entries`.
- Naming: camelCase values/functions, PascalCase types, UPPER_CASE constants; type params `T`-prefixed PascalCase;
  no `I`-prefixed interfaces; single-`*Interface` implementers take the matching suffix.
  `unicorn/prevent-abbreviations` rejects abbreviations in identifiers — write full descriptive names suffixed
  with what they hold (`resolvedPaths`, not `resolved` or `paths`).
- Multi-line `if`: leading `&&`/`||` on continuations. Objects/arrays: ≥4 entries (or already multi-line) break
  one-per-line with trailing comma, all-or-nothing.
- Unions >2 arms: vertical, leading `|`. RegExp literals are not constants
  — inline at the single call site or a `(): RegExp =>` factory, never `UPPER_SNAKE`.
- Extract a helper only at ≥2 call sites or >~50 lines; export only what consumers use.
- `@example` code is linted — when a rule misfires there, disable it in the jsdoc-examples overrides in
  `src/js/eslint/configs/overrides.ts` or `conf/eslint.config.ts` depending on the case, never rewrite the example.

### Docs + docblocks

- List items by shape: definition/continuation bullets → lowercase start, no trailing period;
  standalone facts → capital + period.
- Examples use the Acme universe (vendor `Acme`, modules `User` + `Email`); never `App`.
- Never enumerate exhaustive sets ("all rules", full lists) — they drift. Describe intent; concrete items only as "e.g.".

## Commits

Conventional Commits basics live in `README.md`; commit workflow + git mechanics in `conf/ai/docs/commit.md`.
The rules `commitlint` enforces (`conf/commitlint.config.mjs`):

- Header lower-case, no trailing period, ≤100 chars; subject ≥5 chars.
- Scope required, lower-case, ≥2 chars; delimiters only `/` or `-` (no commas).
- Body sentence-case start, no trailing period, lines ≤100 chars, short.
- Breaking: `!` in subject AND `BREAKING CHANGE:` footer. Never a `Co-Authored-By` footer. HEREDOC for multi-line.
- Validate drafts with `project-commitlint-check`. The user may edit a proposed subject before approving
  — the edited wording wins.

## Dependencies

- After any install: dev deps (composer `require-dev`, bun `devDependencies`) pin exact from the lock file
  (strip the `v` prefix); runtime (`require`, `dependencies`, `peerDependencies`) keep caret ranges.
- Version in `package.json` ⇄ `composer.json` ⇄ `conf/Makefile` (`VERSION`) must match — `project-version-sync-check`.
