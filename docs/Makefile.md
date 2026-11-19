# Makefile [🔍](../conf/Makefile 'Go to source')

A shared `Makefile` for both stacks. Include it and you get a target for every tool your project uses,
a `make help` that lists them, and anything you type after a target is passed on to the tool.
The tool targets are listed per stack: [JavaScript](./js/Makefile.md) and [PHP](./php/Makefile.md).

## Setup

Copy the starter once and let it set up the rest:

```sh
cp -v ./vendor/brnshkr/config/conf/Makefile.dist ./Makefile \
  && make startup
```

In a JavaScript project the starter is `./node_modules/@brnshkr/config/conf/Makefile.dist`.
On a fresh clone, a plain `make` installs your dependencies and runs `make startup` for you.
In CI, where `CI` is set, an install fails on a lock file that no longer matches its manifest instead of rewriting it.

## Usage

```sh
make help                               # everything you can run, add v, vv or vvv for more
make help phpstan                       # only what belongs to phpstan
make check                              # every linter and analyzer, without changing files
make fix                                # every fixer, writing its fixes
make test                               # every stack's tests
make test-matrix                        # the tests once per TEST_MATRIX row
make ci                                 # fix, then check, audit and test, failing at the end
make phpstan src/User                   # anything after a target goes to the tool
make phpunit -- --filter 'User|Email'   # flags go after --
make -j check                           # runs the tools in parallel
```

Most tool targets have variants like `eslint-dry-run` or `phpstan-print`; `make help vvv` lists them all.
An argument with a space in it has to go through `ARGS`: `make phpunit ARGS="--filter 'a b'"`.

## Customizing

Every tool has three variables: its command, its config and its flags,
e.g. `PHP_STAN`, `PHP_STAN_CONFIG` and `PHP_STAN_FLAGS`.
`make help vvv` lists all variables and `make help resolve` shows their values.

Set your own values above the `include` and append to the shipped ones below it:

```Makefile
# ./Makefile

STARTUP_TARGETS := seed

include ./vendor/brnshkr/config/conf/Makefile

PHP_STAN_FLAGS += --xdebug

#-- acme

email-send: #~~ sends a test email, e.g. make email-send info@acme.com welcome
	$(RUN) bin/console acme:email:send $(ARG1) $(ARG2)

seed: #~~ fills the database #env=dev|test
	$(RUN) bin/console acme:user:seed $(ARGS)
```

`#--` starts a section in `make help` and `#~~` describes a target. `#env=` only allows a target in those environments.
If one of your targets has the same name as a shared one, yours wins and the shared one becomes `brnshkr-<name>`.
You can also put targets into any `*.mk` file in `./conf/make/`, or in `./.local/` for ones you don't commit.

To change the shared help, add `#---! brnshkr.theming` to show a section by default, `#---!! brnshkr.theming`
to only show it when named, or `#~~! phpstan-debug` to do the same for a single target.

Each tool reads `./conf/<tool>.dist.<ext>` if you have one, and the package's default otherwise.
`make configs tools` writes them for you. Settings only you want go into `./conf/<tool>.<ext>`,
which git ignores and which is read first.

`make configs` also keeps a block between `###> brnshkr/config ###` markers up to date in files like
`.gitignore`, `.editorconfig` and the VS Code settings. Everything outside that block is yours.

## Containers

If your project has a Compose file, the tools run in its `app` service. Set `APP_SERVICE` above the include
to use another one, or leave it empty to run everything on your machine:

```Makefile
# ./Makefile

APP_SERVICE := php

include ./vendor/brnshkr/config/conf/Makefile

user-import: #~~ imports users
	$(RUN) bin/console acme:user:import

email-queue: #~~ lists queued emails
	$(call run_in,worker) bin/console acme:email:queue
```

The starter installs JavaScript in `JS_SERVICE`, which is `APP_SERVICE` unless you set it. Leave it empty
when your JavaScript tools run on your machine.

`$(RUN)` runs a command where the tools run, `$(call run_in,<service>)` in another service.
`make up`, `make down`, `make shell` and `make <service>-logs` manage the services.

## Test matrix

`TEST_MATRIX` runs the tests against more than your lock files, one row per run:

```Makefile
# ./Makefile

TEST_MATRIX_IMAGE_KEY := ACME_IMAGE

# another toolchain, newer Acme packages and newer frontend packages
TEST_MATRIX += tag[php8.4-bun1.3];composer[acme/*:^2.0];bun[@acme/ui:^3.1&@acme/icons:2.0.0]

# every Composer package at its lowest, except `acme/email` at the highest `^2.4` allows
TEST_MATRIX += composer[*:lowest&acme/email:^2.4]

# only `acme/user` at its lowest
TEST_MATRIX += composer[acme/user:lowest]

# another repository on the same toolchain
TEST_MATRIX += image[ghcr.io/acme/app];tag[php8.5-bun1.4]

# an image of its own
TEST_MATRIX += image[ghcr.io/acme/app:2@sha256:<digest>]
```

Without comments, the rows also fit one assignment:

```Makefile
TEST_MATRIX := \
	tag[php8.4-bun1.3];composer[acme/*:^2.0] \
	composer[*:lowest]
```

A row joins `name[value]` fields with `;`. `image[…]` swaps the repository, or the whole image when it has a tag or digest.
`tag[…]` swaps the tag and keeps its suffix, so `app:1-dev` becomes `app:php8.4-bun1.3-dev`.
`composer[…]` and `bun[…]` take `&`-joined `package:constraint` entries,
where `lowest` is the lowest version the manifest allows, which Bun cannot resolve.
`TEST_MATRIX_IMAGE_KEY` names the variable your Compose file reads the image from.

`make ci` runs every row after the tests, one after another and apart from your own containers.
On your machine, your manifests and lock files are restored afterwards.
For a CI job per row, `make -s test-matrix-rows` prints the rows as a JSON list,
`make test-matrix -- 'composer[acme/user:lowest]'` runs one of them, and `make ci TEST_MATRIX=` skips them.

## Environment files

Loaded the same way `symfony/dotenv` does: `.env`, `.env.local`, `.env.<APP_ENV>` and `.env.<APP_ENV>.local`.
Later files win, and a real environment variable beats all of them.
Before this package is installed, the starter reads them as plain `KEY=value` lines: quotes are stripped,
and neither `${KEY:-default}` nor a `#` inside a value is understood.
Every tracked `.env.<stage>` also gives you `<stage>-<target>`, so `make prod-check` runs `check` with `APP_ENV=prod`.
With a `.env.test`, the tests always run with `APP_ENV=test`, whether you type `make test`, `make ci` or `make phpunit`.

## Changelog

`make changelog` builds the changelog from your commit messages, grouped by scope.

```sh
make changelog              # commits since the last tag
make changelog all          # every release
make changelog write        # into ./changelog/<major>.x.md, keeping your own notes
make changelog notes        # as a release body, ending in the compare link
make changelog write force  # rebuilds each section from git
```
