# PHP tooling — agent notes

## Writing code

- `conf/*.php` are gitignored private halves:
  `make configs local` writes each as an `include` of the tracked `*.dist.php`,
  or as a copy of the package's dist where the project tracks none. `local` writes nothing else.
- Native functions such as `trim`, `json_decode`, `mkdir`, or `shell_exec` are forbidden wherever the component
  replacing them is installed. PHPStan reports the replacement, usually in `Str`, `Json`, Symfony String,
  Filesystem, Finder, or Process.
  A scope every module shares (e.g. `Str`, `ComposerJson`, `Process`) keeps the native call
  behind `@phpstan-ignore symplify.forbiddenFuncCall (…)`,
  or calls the `Str` method wrapping it, such as `Str::split()`.
  The `autoload-dev` directories may call `getenv` and `putenv` and use superglobals.
  Everything else reads the environment through `Str::fromEnvironment()` or `Str::getEnvironment()`.
- Checked exceptions are declared with `@throws`, except in the `autoload-dev` directories.
- An `@internal` symbol is usable at or below its own namespace, or below the namespace written after the tag:
  `@internal Brnshkr\Config`.
- Write `#[\Override]` up front. Rector adds it otherwise.
- A constant glob such as `Module::NAME_*` also matches array constants, so keep arrays out of the prefix.
- `FileFinder` ignores `.gitignore`. Beside its own exclusions, it reads `extra.brnshkr.config.file-finder-excludes`.

## Rules and tests

- A custom rule, fixer or Rector rule follows the `add-custom-rule` skill.
- Fixtures use the vendor `Acme` and are not analyzed.
- A command extending `AbstractCommand` catches its exceptions.
  Assert its exit code and output, never `expectException`.
- Regenerate snapshots with `brnshkr-test-run --doUpdateSnapshots`.
