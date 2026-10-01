# Makefile [🔍](../conf/Makefile 'Go to source')

The package ships one `Makefile`, included from your own, that turns a repository into a task runner for both stacks.
It contributes a target for every tool the repository actually has, a generated `make help` and argument forwarding,
and declares nothing else. Per-stack pages: [JavaScript](./js/Makefile.md) and [PHP](./php/Makefile.md).

```Makefile
# ./Makefile

STARTUP_TARGETS := build

include ./vendor/brnshkr/config/conf/Makefile
```

For a JavaScript package the path is `./node_modules/@brnshkr/config/conf/Makefile`.

Neither path resolves before that stack is installed, so [`./conf/Makefile.dist`](../conf/Makefile.dist)
guards the include and adds a `bootstrap` target that installs and runs `startup`. Copy it to `./Makefile`.

`make startup` installs each stack, writes any file the repository is missing and runs `STARTUP_TARGETS`.
After that, `make` on its own prints the help.

## Targets

`make help` lists what the repository can run: the PHP targets need a `composer.json`, the JavaScript ones
a `package.json`, and each tool's own appear once that tool is installed and the repository has files it reads.
Add `v`, `vv` or `vvv` for more, or a scope to narrow it, e.g. `make help phpstan` or `make help brnshkr.phpstan`.
`make help ls` lists the scopes with something to show at that verbosity,
`make help resolve` prints what each variable expands to,
and `make help env` what the Makefiles export, then what each environment file set and what replaced it.
Across both stacks:

| Target | Description |
| --- | --- |
| `startup` | Installs each stack, writes any missing file, then runs `STARTUP_TARGETS`. |
| `fix` | Runs every fixer and writes its fixes. |
| `check` | Runs every fixer and analyzer without writing anything. |
| `audit` | Checks every stack's locked dependencies for known advisories, run by `ci` too; `composer-audit` and `bun-audit` check one. `COMPOSER_AUDIT_FLAGS` and `BUN_AUDIT_FLAGS` take ignores or a severity floor. |
| `test` | Runs every stack's tests. |
| `ci` | Runs `CI_TARGETS` in order — `check`, `audit`, then `test`, unless set to include `fix` first. |
| `test-update` | Runs them and updates their snapshots. |
| `configs` | Writes any file the repository is missing, `--tools` each tool's config too. Name some, or their files, to write only those, `--force` to overwrite. |
| `pack` | Packs every stack's package into `./.local`. |
| `changelog` | Prints the changelog for `CHANGELOG_RANGE`, or writes it into `CHANGELOG_DIR`. |
| `coverage` | Runs the tests with coverage and fails below `<TOOL>_MIN_COVERAGE`. |
| `group` | Runs every tool that reports identifiers and counts its findings by them, failing only when a tool reports nothing. |
| `cc` | Removes the caches of the tools or files named, or all of them after asking. |
| `fresh` | Removes every ignored file but `FRESH_KEEP` and the private half of each tracked `.dist` file, then reinstalls and runs `startup`, asking first. Untracked files and nested repositories stay too; `--all` removes everything. `--force` skips the question. Needs a commit to reset to. |
| `fresh-dry-run` | Lists what `fresh` would remove and keep. |

`make -j` runs tools in parallel, keeping fixers that write the same files in order.
A verb names each target before running it and carries on past a failing one.
`ci` stops at its first failed step.

A tool's target takes suffixes, the same ones on both stacks:
`-dry-run` writes nothing,
`-list` prints what the tool would read,
`-print` its resolved configuration,
`-group` its findings counted by identifier,
`-debug` runs it verbosely,
`-validate` checks that the rules a tool reads still load,
`-update` rewrites snapshots
and `-coverage` measures how much of the source the tests reach.
`make help` names the ones each tool has.

Anything after a target reaches the tool, so `make phpstan src/Service` and `make composer require symfony/finder`
both work. A word that is itself a target is run as one, except after `help`, `configs` or `cc`,
where every word is a name: `make cc phpstan` clears one cache and runs nothing else,
and a target of your own there is refused. Several targets run in the order given and stop at the first failure,
`make -k` runs the rest.
Make reads a word starting with a dash as one of its own options, so flags go behind `--`:
`make phpunit -- --filter Name`, or `--filter=Name`. Any other `name=value` sets a variable, as `DEBUG=1` does.
A target of your own reads its arguments as `ARGS`, one at a time as `ARG1` through `ARG9`,
and `TARGET` names the target they followed. Each one is quoted, so `make phpunit -- --filter 'A|B'`
reaches the tool rather than the shell. Make splits its command line on spaces before any of this runs,
so an argument that contains one has to come in as `make phpunit ARGS="--filter 'a b'"`.

## Configuration

Every tool is three variables
— the command, its config path and its flags, as in `PHP_STAN`, `PHP_STAN_CONFIG` and `PHP_STAN_FLAGS`.
`make help vvv` lists all of them with what each one does. Set any of them either side of the include; yours wins.

| Variable | Description |
| --- | --- |
| `STARTUP_TARGETS` | The repository's own steps, run last by `startup`. |
| `FIXTURE_TARGETS` | The repository's own steps, run by `fixtures` to build what its tests read. |
| `CHECK_TARGETS`, `FIX_TARGETS`, `GROUP_TARGETS`, `TEST_TARGETS` | The repository's own steps, run by that verb. |
| `APP_SERVICE` | Compose service the tools run in. Empty runs them on this machine. |
| `APP_SERVICE_MODE` | `run` for a throwaway container per command. |
| `RUN` | Prefix the tools run through, built from `APP_SERVICE`. |
| `APP_DIR` | Where the tools see the sources: the service's `working_dir`. |
| `COMPOSE_FILE` | Compose files instead of the repository's, colon-separated. |
| `TARGET_ALIASES` | Short names for targets, as `<alias>=<target>`. `h`, and `-h` behind a `--`, both reach `help`. |
| `TARGET_PREFIX` | Namespaces every shared target. Set it above the include. |
| `COLLISION_PREFIX` | Namespaces a shared target whose name the repository already uses. Set it above the include. |
| `DOTENV` | Base path of the environment files, or `0` to load none. Set it above the include. |
| `PHONY` | Marks every target, the repository's own included. `shared` leaves those unmarked, `0` marks none. Set it above the include. |
| `VENDOR`, `PACKAGE` | What the header and log lines say. Read from the manifest when unset. |
| `VERSION` | What the header shows. `0.0.0-dev` until the repository sets it. |
| `PHP_UNIT_EXCLUDED_GROUPS` | Test groups the runner leaves out, listed before each run. |
| `CACHE_DIR` | Where the tools keep their caches, and what `cc` clears. |
| `FRESH_KEEP` | What `fresh` leaves alone: `./.local`, `*.local` and `*.local.*` files, editor and agent directories. Add a pattern with `+=` below the include, drop one with `FRESH_KEEP := $(filter-out /.idea/,$(FRESH_KEEP))`. |
| `CONFIG` | `local` or `dist` to pin which config every tool reads, instead of the first that is there. |
| `DEBUG`, `TRACE` | `DEBUG` echoes each command, `TRACE` every recipe line. Both make Composer (`-v`, `-vvv` under `TRACE`) and `bun install` verbose. |
| `ANNOUNCEMENT` | What a verb prints before each target it runs, `%s` being the target. Empty silences it. |
| `NO_COLOR`, `FORCE_COLOR`, `CLICOLOR`, `CLICOLOR_FORCE` | Colors, on a terminal or in CI by default. [`NO_COLOR`](https://no-color.org), `CLICOLOR=0` and `TERM=dumb` turn them off, [`FORCE_COLOR`](https://force-color.org) and `CLICOLOR_FORCE` on; off wins. |
| `LOGO`, `THEME`, `EDITOR`, `EDITOR_URL` | How output is printed and where its links point. Empty disables the logo or the links. `EDITOR` is detected when unset, and one inherited from the shell naming another editor is ignored rather than rejected. |

A flag is off when it is empty, `0`, `false`, `off` or `no`, and on for anything else.
`SEMVER_REGEX`, and the four parts it is built from, are there for a repository that has to match a version string itself.
Every tool reads the first config that is there, looking in `./.local/conf/<stack>`, `./.local/conf`,
`./.local/<stack>`, `./.local`, `./conf/<stack>` and `./conf`, then either installation of the package.
`<stack>` is `php` or `js`, and within a directory `<tool>.<extension>` comes before the tracked
`<tool>.dist.<extension>`.
So a repository tracks the `.dist` file and edits that,
a developer who wants private settings adds the undotted one
— which should delegate to the tracked file rather than restate it —
and a repository content with the shipped defaults keeps neither.
`<TOOL>_CONFIG` set by hand skips the search, and `CONFIG=local` or `CONFIG=dist` pins it for every tool
at once — which is how a developer with a private file checks what the gate will read.

`make configs` keeps the package's part of each file between `###> brnshkr/config ###` markers,
refreshed on every run. Everything outside them stays the repository's own and comes after, so it wins.

| File | Package part |
| --- | --- |
| `.gitignore` | caches, dependencies and private configs of what is installed |
| `.gitattributes` | line endings and binaries, plus export rules from the autoload roots and `ARCHIVE_EXTRA_PATHS` |
| `.editorconfig`, VS Code settings and extensions | defaults for the stacks in use; tool config paths follow `<TOOL>_CONFIG`, `css.customData` lists every tracked `.vscode/*.css-data.json` |
| `bunfig.toml` | Bun's defaults, ending in `[install]`: install keys and other tables go below it, top-level keys above |
| `.vscode/tailwind.css-data.json` | the whole file, while Tailwind is installed |
| `./Makefile` copied from `./conf/Makefile.dist` | its two marked parts, around the repository's own targets |

`--tools` also writes each tool's tracked config, `--local` its private half too; `--force` overwrites without asking.
A name writes that config, a file name exactly that file: `make configs phpstan.php` is the private half alone.

## Changelog

`make changelog` prints one release from `git log`, newest first, installing nothing.
Headings are the **scope root** rather than the commit type, and a nested scope becomes a sub-heading
— `js/eslint/rule` reads as `rule` under `ESLint` under `☕ JS`.
Every argument takes a short form and works without dashes: `--all`, `-a`, `all` and `a` are the same.

| Argument | Does |
| --- | --- |
| none | the commits since the last tag |
| `--all` | every release, one section each |
| `--write` | merges into `CHANGELOG_DIR` rather than printing |
| `--notes` | a release body: no version heading, ending in the compare link |
| `--force` | with `--write`, regenerates edited entries |

`--write` merges into `changelog/<major>.x.md`:
your notes stay, a missing commit lands after the last entry of its group, and a missing title is restored.
Below `1.0.0` it asks first.
Breaking commits are never skipped.

## Containers

With a compose file, the tools run in its `app` service, started on first use:

```Makefile
APP_SERVICE := tools
```

Paths map back to your checkout, and inside the container make runs everything directly.
Declared `.env` keys travel with each command by name, so their values stay out of `DEBUG` output and `ps`;
command-line variables travel with their value.
The compose file itself may interpolate any key the environment files set, `.env.local` and stage files included.
Own recipes take `$(RUN)`, another service `$(call run_in,<service>)`.

| Target | Does |
| --- | --- |
| `up`, `down`, `ps` | starts, stops and lists the services |
| `shell`, `exec` | a shell, a command in `APP_SERVICE` |
| `<service>-shell`, `-logs`, `-exec`, `-build` | a shell, the log, a command in that service, its image |

`up`, `down`, `-build` and `-logs` take their flags from `COMPOSE_UP_FLAGS` and its siblings,
`SERVICE_SHELL` names the shell.

## Environment files

A `.env` is loaded the way `symfony/dotenv` loads it, so one set of files serves PHP, make and anything make runs.

- Load order is `.env`, `.env.local`, `.env.<APP_ENV>`, `.env.<APP_ENV>.local`.
  Later wins, and a real environment variable beats all. `DOTENV_ENV_KEY` renames the variable.
- `.env` is tracked and holds what is true everywhere.
  A repository that would rather not track it ships `.env.dist` instead, read only when `.env` is absent.
- One `.env.<environment>` per environment may be tracked, each with its own untracked `.env.<environment>.local`.
  `DOTENV_DEFAULT_ENV` is the environment assumed when the variable is unset, and `DOTENV_TEST_ENVS`
  lists every environment that skips `.env.local` — a test run has to be reproducible — while still
  reading its own `.env.<environment>.local`.
- Syntax follows Symfony's parser, except `$(command)`, which is refused.
- Every tracked `.env.<environment>` also gives you `<environment>-<target>`, so `make prod-check` is
  `make APP_ENV=prod check`. `.env.local` and any `.example` are not environments.

## Your own targets

Write them in any `Makefile` or `*.mk` at the root or under `./conf/`, `./conf/make/`, `./.local/`, `./.local/make/`,
`./.local/conf/` or `./.local/conf/make/`.
They are read in that order, so `./.local/` overrides what the repository ships.

Reusing a name is fine: yours keeps it and the shared one moves aside,
so a `check` of your own leaves `brnshkr-check` beside it.
Should that name be taken too, the shared one is numbered — `brnshkr-check-1` — and `make help` names it.
`TARGET_PREFIX := brnshkr` moves all of them aside at once,
making `make check` into `make brnshkr-check`;
the separator is added for you and your own targets keep their names.

Your own Makefile can reshape the shared help: `#---! brnshkr.theming` shows that scope by default,
`#---vv! brnshkr.theming` from `vv`, `#---!! brnshkr.theming` only when named.
`#~~! phpstan-debug` does the same for a target, variable or function.
The most specific line wins; unknown names, wrong depths and duplicates fail `make help`.
