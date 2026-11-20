# Code

**Less code is better code**, above all on the consumer's side.
After that, in order:
simplicity, flexibility, maintainability, readability, brevity, correctness, stability, good UX and DX, and performance.

Read the closest sibling and the active lint rules before writing a new file.
Write to them from the first line, and mirror the nearest existing feature's layout before inventing one.

## Comments

- Default to zero comments. A better name, a smaller function or a clearer type comes first.
  Never add one to explain an edit, and never re-add one an earlier edit removed.
- Only functional comments survive:
  `@phpstan-ignore <identifier> (Reason)`, and `/* eslint-disable … -- Reason */` with its matching `eslint-enable`.
  The `eslint-enable` takes no reason.
- `NOTICE:` marks an inline `//` comment on one surprising line, nowhere else.
- A suppression comment such as `# nosemgrep: <rule>` sits on its own line above the line it suppresses.
- No new `eslint-disable`, `@phpstan-ignore`, `@ts-expect-error` or `ignores` entry. Restructure instead.
- A docblock opens with a verb for what the symbol does. Only public API gets a summary and tag descriptions.
  A private symbol gets only the types PHPStan needs.

## Shape

- Prefer `<const>[…] satisfies SomeType` to `const x: SomeType = […]`,
  and derive a type from its value: `type X = typeof XS[number]`.
- A regular expression is inlined or comes from a factory, never a constant:
  `const createHeredocPattern = (): RegExp => /…/dgv;`.
- No `switch`. Write an `if`/`else if` chain that returns early, or a mapping object keyed by the discriminant.
- A TypeScript function takes named parameters and reads them with dot notation:
  `(release: NpmRelease, registry: NpmRegistry)`, never `({ name }: NpmRelease)`.
- An array or object with four or more entries, or one already on several lines, takes one entry per line
  and a trailing comma, all entries or none: `[\n    'git',\n    'ls-files',\n    '-z',\n]`.
- A wrapped condition starts on the `if (` line, opens each continuation with `&&` or `||`,
  and closes with `) {` on the last one: `if (!is_file($path)\n    || !is_readable($path)) {`.
- Inside a multi-line literal, every nested object takes one key per line, a single key included:
  `packageJsonPath: {\n  type: 'string',\n},`.
- An unreachable fallback throws through a shared helper instead of defaulting with `?? ''`.
  Each helper's throw gets one test.
- Extract a helper at two or more call sites, named for what it does.
- Long, descriptive names, suffixed with what they hold: `$pathsTheFixturesWrite`, `resolvedPaths`.
  Methods take the codebase's verbs: `getX` for a query, `isX` for a predicate, `forX` or `fromX` for a factory.
- Parallel reads take `Promise.all`, parallel side effects `Promise.allSettled`.
- A normalizing setter rejects what it cannot use. It never drops it silently.
- Pin a default our setup depends on, even where it equals upstream's:
  a mounted path, a published port, the user we run as. An option we merely accept stays implicit.
- Files doing the same job stay in parity:
  the same guard, wrapping and order, even where a guard is unreachable in one of them.
- A method without a caller may exist for parity with a sibling repository. Grep the siblings before removing it.
- A relative path carries `./` in code, workflows and Dockerfiles. A glob stays in the form its tool documents.
- Manifest keywords run `dev` (a dev package only), `brnshkr`, the project's name, then the rest alphabetically.
- A tool prints for people by default: `name: from -> to`.
  Machine output needs an argument (`--format=tsv`), and nothing writes on its own in CI.

## PHP

- Three nested `array_*` calls become a `|>` pipeline, one step per line.
- Once inside an `s()` chain, keep the Symfony String fluent API and an `AbstractString` until a string is needed.
  Avoid a regular expression where a string operation will do.
- One `@throws` per cause, as Symfony's Filesystem writes them.
- A PHPDoc array shape with keys is multi-line, one key per line, with a trailing comma.
- A `sprintf` building an exception message takes the format and every argument on lines of their own.
- A call's only argument never moves to a line of its own.
- Blank lines group by kind:
  none between consecutive single-line statements of one kind, one around a multi-line statement or block.
  A multi-line statement never joins an aligned group.
- Never promote a property that has a hook.
- Test hooks are `#[Before]` and `#[After]` on public, descriptively named methods, never `setUp` or `tearDown`.
- A file without a namespace tags its symbol where it can:
  a file whose body is one `return` carries `@internal <root>` on that `return`.

## Errors and messages

- A thrown error reads like Symfony's, in TypeScript and PHP alike:
  values in double quotes, a period, and wording found in `vendor/symfony`,
  e.g. `Unsupported format "markdown".` or `Unable to write file ".env": …`.
- A shell error reads like the shared Makefile's: terse, values in backticks, and a period,
  e.g. ``Unknown step `%s`.``

## Makefile

- A target that performs an action is named for it, in the singular (`check`, `fix`).
  One that manages a set of files is named for the files, in the plural (`configs`).
- Every variable is UPPER_CASE, and every lowercase name is a function:
  a `define` with a `#**` docblock whose first line is one short sentence standing on its own.
- Each assignment group aligns its operators one space past its longest name.
  A target's own settings sit directly above its rule.
- `;` goes only where the parser needs it: between statements on one line, and before a `}` closing on the same line.
- Lists and the arguments of a wrapped `$(or \` or `$(and \` take one item per line.

## Shell

- POSIX `sh`, never bash, running under dash and busybox, with every ShellCheck check enabled.
- 2-space indent.
  Every `case` arm sits on its own lines, with `;;` on a line of its own, a blank line between arms,
  and a `*)` arm in every `case`.
- `if ! is_x "${value}"; then … fi` over `cmd || other`, and an explicit `return 0` ending a predicate.
- Each function first assigns its positional parameters to descriptive names: `registry=${1}`.
- Constants are UPPER_SNAKE. No `local`. Braces on every variable reference.
- Assign a `$(…)` before using it, never inside another command's arguments.
- A script that tests its own functions in conditions drops `set -e` and keeps `set -u`.
  Each command in it that can fail ends in `|| exit 1`.
- Builtins over pipelines: `case` with `[:lower:]`-style classes instead of `grep`.
- A redirect takes no space: `>>"${GITHUB_OUTPUT}"`, `>/dev/null`.
- Shell and awk names follow the PHP and JavaScript ones: a map is `value_by_key`, a set a participle and a plural.
