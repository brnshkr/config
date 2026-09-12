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
guards the include and adds a `bootstrap` target that installs and runs `startup`.
`composer brnshkr:config:setup --make` writes it; a JavaScript repository copies it.

`make startup` installs each stack, writes any tool config the repository is missing and runs `STARTUP_TARGETS`.
After that, `make` on its own prints the help.

## Targets

`make help` lists what the repository can run: the PHP targets need a `composer.json`, the JavaScript ones
a `package.json`, and each tool's own appear once that tool is installed and the repository has files it reads.
Add `v`, `vv` or `vvv` for more, or a scope to narrow it, e.g. `make help brnshkr.phpstan`
— a bare `phpstan` is a target, and naming one runs it.
`make help ls` lists the scopes and `make help resolve` prints what each variable expands to.
Across both stacks:

| Target | Description |
| --- | --- |
| `startup` | Installs each stack, writes any missing tool config, then runs `STARTUP_TARGETS`. |
| `fix` | Runs every fixer and writes its fixes. |
| `check` | Runs every fixer and analyzer without writing anything. |
| `test` | Runs every stack's tests. |
| `ci` | Runs `CI_TARGETS` in order — `check` then `test`, unless set to include `fix` first. |
| `test-update` | Runs them and updates their snapshots. |
| `configs` | Writes any tool config the repository is missing, and never touches one it has. |
| `pack` | Packs every stack's package into `./.local`. |
| `coverage` | Runs the tests with coverage and fails below `<TOOL>_MIN_COVERAGE`. |
| `group` | Runs every tool that reports identifiers and counts its findings by them. |
| `cc` | Removes cached tool state, naming what it will remove and asking first. |

`make -j` runs tools in parallel, keeping fixers that write the same files in order.

A tool's target takes suffixes, the same ones on both stacks:
`-dry-run` writes nothing,
`-list` prints what the tool would read,
`-print` its resolved configuration,
`-group` its findings counted by identifier,
`-debug` runs it verbosely,
`-update` rewrites snapshots
and `-coverage` measures how much of the source the tests reach.
`make help` names the ones each tool has.

Anything after a target reaches the tool, so `make phpstan src/Service` and `make composer require symfony/finder`
both work. A word that is itself a target is run as one, which is why `make cc phpstan` runs both rather
than clearing one cache. Several targets run in the order given and stop at the first failure, `make -k` runs the rest.
Make claims two shapes for itself: `name=value` becomes a variable of its own,
and anything starting with a dash becomes one of its own options.
Write the value with a space, and put `--` in front of the flags: `make phpunit -- --filter Name`.
A target of your own reads them as `ARGS`, one at a time as `ARG1` through `ARG9`,
and `TARGET` names the target they followed.

## Configuration

Every tool is three variables
— the command, its config path and its flags, as in `PHP_STAN`, `PHP_STAN_CONFIG` and `PHP_STAN_FLAGS`.
`make help vvv` lists all of them with what each one does. Set any of them either side of the include; yours wins.

| Variable | Description |
| --- | --- |
| `STARTUP_TARGETS` | The repository's own steps, run last by `startup`. |
| `RUN` | The command the tools are run through, such as `docker compose exec app`. |
| `WORKDIR` | Where the tools see the sources, `/app` under `RUN`. |
| `TARGET_PREFIX` | Namespaces every shared target. Set it above the include. |
| `COLLISION_PREFIX` | Namespaces a shared target whose name the repository already uses. Set it above the include. |
| `DOTENV` | Base path of the environment files, or `0` to load none. Set it above the include. |
| `PHONY` | Marks every target, the repository's own included. `shared` leaves those unmarked, `0` marks none. Set it above the include. |
| `VENDOR`, `PACKAGE` | What the header and log lines say. Read from the manifest when unset. |
| `VERSION` | What the header shows. `0.0.0-dev` until the repository sets it. |
| `CACHE_DIR` | Where the tools keep their caches, and what `cc` clears. |
| `CONFIG` | `local` or `dist` to pin which config every tool reads, instead of the first that is there. |
| `DEBUG`, `TRACE` | Echo each command as it runs. `TRACE` echoes the guards along with them. |
| `LOGO`, `NO_ANSI`, `THEME`, `EDITOR`, `EDITOR_URL` | How output is printed and where its links point. Empty disables the logo or the links. `EDITOR` is detected when unset, and one inherited from the shell naming another editor is ignored rather than rejected. |

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

`make configs` writes what the repository is missing: each tool's tracked config half, and a `.gitignore`.
Each comes from the package, or from the repository's own `./conf/<name>.example` where it keeps one,
and a copied PHP config is given the project's root namespace in its `@internal` tag.
`make configs local` writes the private halves as well, each delegating to the tracked file rather than
restating it: an `include` for a PHP config, an `export { default } from` for a JavaScript one.
A recipe whose config is missing everywhere names the path it wants and the variable it came from.

## Containers

Say how the tools are run and nothing else changes:

```Makefile
RUN := docker compose exec app
```

Paths reported from inside the container are mapped back to your checkout,
so editor links and error messages point at files you can open.
`RUN` is ignored when make is already inside the container, so one line serves both.

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

## Your own targets

Write them in your `Makefile`, or in any `Makefile` or `*.mk` under `./conf/`, `./conf/make/`, `./.local/` or `./.local/make/`.
They are read in that order, so `./.local/` overrides what the repository ships.

Reusing a name is fine: yours keeps it and the shared one moves aside,
so a `check` of your own leaves `brnshkr-check` beside it.
Should that name be taken too, the shared one is numbered — `brnshkr-check-1` — and `make help` names it.
`TARGET_PREFIX := brnshkr` moves all of them aside at once,
making `make check` into `make brnshkr-check`;
the separator is added for you and your own targets keep their names.
