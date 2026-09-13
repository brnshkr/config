# PHP tooling — agent notes

## Writing code

- `conf/*.php` are gitignored private halves that `make configs local` writes as an `include` of the tracked `*.dist.php`.
- Native functions such as `trim` or `json_decode` are forbidden. PHPStan names the replacement, usually in `Str`, `Json`
  or Symfony String.
- Checked exceptions are declared with `@throws`, except in the `autoload-dev` directories.
- An `@internal` symbol is usable at or below its own namespace, or below the namespace its tag names.
- Write `#[\Override]` up front; Rector adds it otherwise.
- A constant glob such as `Module::NAME_*` also matches array constants, so keep arrays out of the prefix.
- `FileFinder` ignores `.gitignore`; its exclusions are hardcoded.

## Rules and tests

- A PHPStan rule is a class in `src/php/PhpStan/Rule/`, a test in `tests/php/PhpStan/Rule/`,
  fixtures in `tests/php/Fixtures/PhpStan/Rule/<Name>/` and a page `docs/php/phpstan/rules/<Name>.md`.
  Check the pairing with MCP `project-rule-docs-audit`.
- A rule test marks expected errors with `// ERROR <context>` in its fixture. A rule reporting two errors on one line
  uses PHPStan's `RuleTestCase` with an explicit list instead.
- Fixtures use the vendor `Acme` and are not analyzed.
- A command extending `AbstractCommand` catches its exceptions, so assert its exit code and output, never
  `expectException`.
- Regenerate snapshots with MCP `project-tests-run` and `doesUpdateSnapshots=true`.
