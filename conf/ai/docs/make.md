# Make tooling — agent notes

Agent knowledge beyond `docs/php/Makefile.md` (downstream usage) and the `conf/Makefile` header comment (annotation syntax, targets, variables).

## help-AWK internals

- The AWK script reads runtime config via `ENVIRON[...]`. `_make_vars_as_env` exports every simple/recursive Make var matching `^_?[A-Z][A-Z0-9_]*$` (skips lowercase `define` macros, auto-vars, non-identifier names; newlines flattened). Vars needing transformation are exported explicitly per recipe line (`_PWD`, `_HEADER_*`, `_SCOPES`, `_VERBOSITY`, ...).
- Bootstrap tools `_AWK`/`_PRINTF`/`_MAKE` (`$(or $(AWK),awk)`, lazy `=`) sit BEFORE the auto-include block — user Makefiles can auto-include before the tools section sets `AWK ?= awk`. Underscore prefix hides them from help.
- Condition eval: `ifdef` checks `ENVIRON`; `ifndef` always returns 1 (foreach exports final var state — strict eval would hide blocks); `ifeq`/`ifneq` use depth-aware paren/comma parsing + `expand_make` against ENVIRON. `condition_depth` stack + branch arrays handle nesting and `else ifeq` chains. `endif` decrements depth only — must NOT reset `scope_depth`. `add()` dedups via `seen_symbol[scope, section, name]` (first branch wins).
- Verbosity regexes use `v+` (arbitrary depth). CLI verbosity = longest `^-?v+$` token in ARGS, or `V=N`; `_HELP_VERBOSITY_ARGS` filters v-args out of scope filtering.
- Logo rendering bypasses `$(call text,...)` (it strips leading whitespace); AWK uses `rtrim` (not `trim`) for logo/title.
- `PWD := $(CURDIR)` is set BEFORE the auto-include block (it feeds the include paths) and overrides the env variable, so paths follow `-C` instead of the shell's PWD.
- mawk-compatible: POSIX only, no `{m,n}` interval quantifiers.
- Editor hyperlinks: entries wrapped in OSC 8 escapes from `_EDITOR` URL templates (`vscode`, vscode+WSL via `WSL_DISTRO_NAME`, `phpstorm`); skipped under `NO_ANSI`.

## MakeHelpTest (`tests/php/MakeHelpTest.php`)

- Runs `make help --no-print-directory` via Process against `tests/php/Fixtures/Make/`, `NO_ANSI=1`, snapshots to `__snapshots__/MakeHelpTest__testHelpOutput__1.txt`.
- Path normalization replaces ONLY the repo root (→ `.`) — also replacing the fixture dir would collapse distinct paths.
- `renderDeduplicatedScenarios()` hashes scenario outputs, emits each unique once with `(also: alias...)` header.
- Process env is sanitized (`MAKEFLAGS` cleared, `WSL_DISTRO_NAME`, `TERM_PROGRAM`, `TERMINAL_EMULATOR`, `EDITOR` emptied) for determinism. Clearing `MAKEFLAGS` matters because a command-line variable of an outer `make` travels in it and overrides the scenario env. The fixture dir comes from `-C`, not from a `PWD` env entry — `PWD := $(CURDIR)` ignores the env.
- The fixture Makefile is the canonical feature surface (scope depths, verbosity gates, var operators, skipped `_private`/`.dot` symbols, condition blocks, define-doc variants) — new help features need a representative fixture entry.
- Fixture naming: `<dir>-<file-type>-command`; no abbreviations (`command`, not `cmd`); visibility prose `shown at verbosity >= N`.
- Run: `make test -- --filter MakeHelpTest`; regen: `make test-update -- --filter MakeHelpTest`.
