# Make tooling — agent notes

Usage is [`docs/Makefile.md`](../../../docs/Makefile.md); make and awk traps are in `vision/TRAPS.md`.

## Writing it

- Shared targets are `$(call _target,name)`, private ones `_$(_TARGET_PREFIX)name`.
- A tool section is gated on its binary and adds itself to the lists it belongs to, such as `_ANALYZER_NAMES`.
  It gets a suffix like `-dry-run` or `-group` only when the tool has that feature.
- Commands take `$(DEBUG_PREFIX)`, guards `$(TRACE_PREFIX)`, recursive calls `$(_MAKE_FLAGS)`, verbs `$(_VERB_FLAGS)`.
- `$(ARGS)` goes before a trailing flag, and is quoted for the shell; a `$(filter)` or a macro reads `$(_ARGS)`.
- A sub-make re-reads every Makefile from disk, so `fresh` reinstalls before it recurses — the clean can
  take the included one with it — and passes `_IS_INSTALLING=1` so `startup` skips the install it just did.
- A piped tool runs through `_capture`, so a crash fails the recipe.
- A `log` message is a `printf` format; pass values as arguments.
- awk is POSIX only and must print the same under gawk, mawk and busybox awk.
- Verbosity: 0 is the everyday targets, 1 their extras, 2 lists and prints, 3 internals.
  A variable sits with the target it configures, and a level every entry of a scope shares goes on the scope.

## Testing it

- `make phpunit -- --filter MakefileTest`; `phpunit-update` regenerates the help snapshot.
- A help feature gets an entry in `Fixtures/Make/Help/`.
- Clear `MAKEFLAGS` when a test runs make.
