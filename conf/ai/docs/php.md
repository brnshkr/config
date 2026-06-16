# PHP tooling — agent notes

Agent knowledge beyond `docs/php/`.

## Local tool configs

`conf/{phpstan,php-cs-fixer,rector,twig-cs-fixer}.php` are gitignored working copies created from the committed `*.example` files (see `docs/development.md` setup). Make targets and the `phpstan-analyse` MCP tool (`configuration=conf/phpstan.php`) resolve against these, not the `*.dist.php` shipped defaults.

## Writing conforming code

- Many native functions are forbidden (symplify `forbiddenFuncCall`) in favor of wrappers — e.g. `trim`/`strlen` → `Brnshkr\Config\Str` helpers or Symfony String `s()`, `json_decode` → `Brnshkr\Config\Json::decode`, `file_get_contents` → `Filesystem::readFile`. The PHPStan error names the expected replacement; check `Str`/`Json` for an existing helper first.
- Checked exceptions must be declared (`missingType.checkedException`) — Symfony `Process` alone adds `LogicException`/`RuntimeException` `@throws` to every caller chain.
- `@internal` symbols are only usable within their root namespace (`InternalUsageRule`); an explicit target (`@internal Vendor\Package`) widens the audience deliberately.
- Rector rewrites on `make rector`: imports FQCNs (no fully-qualified inline names), adds `#[\Override]` to overridden methods, adds `#[\SensitiveParameter]` to secret-named params (`password`, `apiToken`, ...) — generate code that way up front. Full builder behavior in `docs/php/Rector.md`.

## Composer plugin

- Plugin commands: `php scripts/composer.php list` → `brnshkr:config:{setup, print-module-config, extract-phar, update-php-extensions}` (also reachable through `composer` in consuming projects).
- The module registry (`src/php/Module.php`) maps the four tool modules to required/optional packages — MCP `project-modules-list`/`project-module-config` expose it.

## Custom PHPStan rules

- New rule = class in `src/php/PhpStan/Rule/` + Pest test in `tests/php/PhpStan/Rule/` + fixtures in `tests/php/Fixtures/Rule/<Name>/` + doc page `docs/php/phpstan/rules/<Name>.md` (PascalCase). Verify pairing with MCP `project-rule-docs-audit`.
- Architecture rules are PHPat-based `*Test` classes under `src/php/PhpStan/Rule/Architecture/<Framework>/`, bundled through the `Architecture` facade factories (`layered`, `ddd`, `symfony`, ...).
- Constant globs like `Module::NAME_*` match ALL constants with that prefix, array constants included — an array in the glob expands the type to `string|array<...>` and breaks `key-of<>`. Rename the odd constant out of the prefix or use `key-of<self::EXPLICIT_MAP>`.
- `InternalUsageRule` emits max one violation per statement; pre-order traversal means the deepest accessed symbol wins (`Foo::method()->path` reports the property fetch). Allow-list options accept a plain namespace prefix or a `/regex/`.
- `FileFinder` does NOT respect `.gitignore`; its exclusions are hardcoded — see `docs/php/FileFinder.md`.

## Tests + snapshots

- Prefer MCP `project-tests-run` with `doesUpdateSnapshots=true` for regeneration — it reports which snapshot files changed (commands: `docs/development.md`).
- `PrintModuleConfigCommandTest` snapshots embed resolved config file path lists — adding/removing PHP files under `conf/` changes them by design.
- Test fixtures (`tests/**/Fixtures`) are excluded from analysis; like the rest of the project they use the Acme universe (vendor `Acme`, modules `User`/`Email`).
- Commands extending `AbstractCommand` swallow exceptions (`execute()` wraps `wrappedExecute()` in try/catch + Console output). Tests assert `$exitCode !== 0` + buffered output text, never `expectException`.
