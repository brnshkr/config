# Make tooling — agent notes

What is not readable off `conf/Makefile` itself. Usage is [`docs/Makefile.md`](../../../docs/Makefile.md);
the general make and awk traps are in `vision/TRAPS.md`.

## Order

- `_BRNSHKR_CONFIG_MAKEFILE` is taken before the includes, `_CONSUMER_MAKEFILES` after them — which is what
  makes a target in `conf/make/*.mk` the consumer's, and visible to both guards.
- `.DEFAULT_GOAL` is captured before and resolved after, so the consumer's goal wins.
- `RM` and `SHELL` are `:=` behind an `$(origin)` guard: make predefines them, and a project's value has to
  survive on either side of the include. `MAKE` stays make's own.
- One awk scrape of `$(MAKEFILE_LIST)` yields `target|file` pairs and every target list derives from it. It
  evaluates `ifneq ($(wildcard …),)` itself and depth-tracks the rest, keeping both branches. An empty
  scrape means `AWK` is not an awk, which is what the guard under it reports.
- `_CONFIG_TARGETS` is recursive, so a config path set after the include still wins.

## Naming

- Shared targets are written `$(call _target,name)`, private ones `_$(_TARGET_PREFIX)name` — the leading `_`
  is what makes help skip them.
- `_target` takes the first name the consumer has not: its own, then behind `COLLISION_PREFIX`, then that
  numbered `-1` to `-9`. The help awk reads the resolved pairs from `_TARGET_NAMES` instead of repeating it.
- The suffixes are fixed — `-dry-run`, `-list`, `-print`, `-group`, `-debug`, `-update`, `-coverage`,
  `-pack`, `-raw` — and a tool gets one only when it has that feature. Nothing is emulated.

## Gating

- The extensions a tool reads are one variable each: `PHP_EXTENSIONS` for all four PHP tools,
  `TWIG_CS_FIXER_EXTENSIONS`, `TYPESCRIPT_EXTENSIONS`, and the JS ones that follow installed packages.
  Anything shared by a stack's sections is declared above them — `PHP_EXTENSIONS` under the PHP gate,
  `BUN` and `BUN_FLAGS` under the JS one.
- A macro sits as low as make allows, which is the helpers block for anything a recursive variable or a
  recipe calls. `_when_tracked` and `_when_installed` cannot: a `:=` list appends through them, so they sit
  right above the first section that does, under a repeated `#---v helpers` label.
- `_TRACKED_EXTENSIONS` is one `git ls-files`. Every membership list and every config entry goes through
  `_when_tracked`, so `ci` cannot demand a config `configs` declined to write. Empty means git could not
  say, so everything stays.
- `_config` is the read side: the first candidate that exists, then those names under
  `_PACKAGE_CONF_DIRS`, falling back to the last candidate — which is what the guard then tells the project
  to create. Candidates are spelled out at each call site rather than derived, so a tool with no `.dist`
  half names one path and a layered tool names two.
  `CONFIG` pins the choice to the first or last candidate and skips the search, so the guard reports the
  pinned file when it is not there.
- `eslint` and `vitest` list their `.ts` config first, which is why this repository needs no override for
  either; the other JS tools cannot load TypeScript and their `.mjs` jiti-loads it instead.
- `configs` is the write side and follows each tool's own `<TOOL>_CONFIG`, so an override is respected.
  `_LAYERED_CONFIGS` goes through `_tracked` — idempotent, so a variable already resolved to the `.dist`
  file stays put — and the `local` argument adds the undotted halves; `_PLAIN_CONFIGS` is written as it
  stands. Each comes from `<config>.example` in the project, then from each of `_EXAMPLE_DIRS`. Anything
  keyed to tests waits for a `tests` directory, or a run creates what the next keys off.

## Recipes

- Recipes take `$(DEBUG_PREFIX)`, guards `$(TRACE_PREFIX)`, recursive calls `$(_MAKE_FLAGS)`.
- `log` renders backticks as inline code and its message is a `printf` format: anything that could hold a
  `%` goes in as an argument. `_named_path` names a path in one, relative to the checkout.
- A piped tool runs through `_capture`, or the pipe reports awk's status and a crash reads as an empty list.
  `_group` prints its table first and re-raises the status, since a tool with findings exits nonzero; the
  `group` aggregate runs its children under `--keep-going` for the same reason.
- A `-group` table names the tool flush, like a help section label, indents its rows one step, and colors
  the count with `COLOR_ENTRY` rather than `COLOR_HIGHLIGHT` — the highlight is the section color under the
  symfony theme, where the two would read as one.
- `NO_ANSI` lives in each tool's `*_FLAGS` default, `_NO_ANSI_OPTION` being the Symfony-console spelling.

## Per tool

- `Makefile.example` bootstraps on the host, so its `composer install` passes `--no-scripts`
  `--ignore-platform-reqs`: it only has to make the include resolvable, and `startup` installs again through
  `RUN`, where the platform is real.
- A `pest` target is a guarded alias re-entering the `phpunit` one with `PHP_UNIT` set to `PEST`, so
  `_IS_PEST` — the resolved variable, not the filesystem — and the flags branching on it follow.
- `PHP_UNIT_SNAPSHOT_FLAGS` carries `--do-not-fail-on-incomplete`: a rewritten snapshot reports incomplete.
- PHPUnit merges nothing — one file, no `extends` — so `conf/phpunit.xml` replaces the `.dist` one rather
  than layering on it the way a PHP config does.
- Consumers get `conf/vitest.config.mjs`; this repository keeps a `.ts` one through a `VITEST_CONFIG`
  override, whose `testTimeout` is deliberately not in the example.

## The awk programs

- POSIX only: no `{m,n}`, no `%*s`. Verified against gawk, mawk and busybox awk.
- `_HELP_AWK` reaches its recipe through `export`. `_DOTENV_AWK` runs at parse time, so it is inlined in
  single quotes, may contain none, and doubles every `$` — error messages included — because `$(eval)`
  expands its output once more.
- `EDITOR ?= $(_DETECTED_EDITOR)` is the whole editor logic: unset takes the detection, an explicit empty
  disables the links, a known name wins, and an unknown one warns — except from the environment, where
  `EDITOR` is the standard shell variable and anything unrecognized is ignored. An `?=` cannot sit above an
  `$(origin …),undefined` conditional; it would define the variable and make that branch unreachable, and a
  variable declared inside one never reaches help, which evaluates conditionals and keeps the live branch.
- Verbosity is a ladder, not a sum: a `#v` inside a `#---v` scope still shows at 1. Level 0 is `help`,
  `check`, `test` and each tool's own target; 1 adds the everyday extras — `startup`, `configs`, `cc`,
  `group`, `test-update`, every `-dry-run`, `-group`, `-update` and `-coverage` — and each tool's command,
  config and flags; 2 adds `ci`, `coverage`, every `-list` and `-print`, and the knobs behind them, the
  extension lists and globs among them; 3 is what is rarely reached for: `pack`, every `-debug` and `-raw`,
  the `pest` aliases and the internals.
- A variable never surfaces before the target it configures. `PEST` sits at 3 because every `pest` target
  does, and a glob sits with the extension list it is built from rather than a level above it.
- Help config arrives as `ENVIRON[...]`: `_make_vars_as_env` exports everything matching
  `^_?[A-Z][A-Z0-9_]*$`, the rest go per recipe line. `ifndef` always reads true there, and renders `?=`.
- `_RESOLVE` swaps a variable's source text for a trimmed `ENVIRON[name]`, in the column measurement too;
  a value built from an empty `RUN` would otherwise start with a space.
- The END block refuses a top-level scope whose name is in `_HELP_WORDS`, which would otherwise be
  unreachable in every spelling.
- `endif` decrements depth only, never resets `scope_depth`. `add()` dedups on `seen_symbol`, first branch
  wins. Only a `#-` marker changes scope. Logo and title use `rtrim`, bypassing `$(call text,…)`.
- `_GROUP_AWK` reads `"identifier":"…"` with a message, a bare list such as `appliedFixers`, or — with no
  identifier key — reporter text, `error <rule> <message>`, whose colon after the rule is optional so both
  `tsc` and `markdownlint-cli2` land. Keys tolerate whitespace after the colon, and it sorts by count itself
  rather than depending on `sort`.

## MakefileTest

- `Fixtures/Make/Help/` is the help snapshot's feature surface: a new feature needs an entry there, named
  `<dir>-<file-type>-command`, no abbreviations, visibility prose `shown at verbosity >= N`. `Help/make/` is
  outside the include set and asserted absent.
- `ConfigFallback/` includes the shared file through a symlink under `node_modules/@brnshkr/config/conf` —
  the only way `_BRNSHKR_CONFIG_DIR` is not this repository.
- `ConfigVendor/` keeps no config of its own, so the search has to reach `vendor/brnshkr/config/conf`;
  `ConfigFallback/` must therefore keep only `.example` files there, or the search satisfies itself and the
  example hunt never runs.
- One hook runs `#[Before]` as well as `#[After]`, so an interrupted run cannot fail the next.
- Assert names with the symbol helpers, and clear `MAKEFLAGS`: an outer make's command-line variables travel
  in it. The fixture directory comes from `-C`.
- Run `make phpunit -- --filter MakefileTest`; regenerate with `phpunit-update`.
