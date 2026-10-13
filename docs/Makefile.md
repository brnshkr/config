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

## Usage

```sh
make help                               # everything you can run, add v, vv or vvv for more
make help phpstan                       # only what belongs to phpstan
make check                              # every linter and analyzer, without changing files
make fix                                # every fixer, writing its fixes
make test                               # every stack's tests
make ci                                 # check, audit and test, stopping at the first failure
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
`make configs --tools` writes them for you. Settings only you want go into `./conf/<tool>.<ext>`,
which git ignores and which is read first.

`make configs` also keeps a block between `###> brnshkr/config ###` markers up to date in files like
`.gitignore`, `.editorconfig` and the VS Code settings. Everything outside that block is yours.

## Containers

If your project has a compose file, the tools run in its `app` service. Set `APP_SERVICE` above the include
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

`$(RUN)` runs a command where the tools run, `$(call run_in,<service>)` in another service.
`make up`, `make down`, `make shell` and `make <service>-logs` manage the services.

## Environment files

Loaded the same way `symfony/dotenv` does: `.env`, `.env.local`, `.env.<APP_ENV>` and `.env.<APP_ENV>.local`.
Later files win, and a real environment variable beats all of them.
Every tracked `.env.<stage>` also gives you `<stage>-<target>`, so `make prod-check` runs `check` with `APP_ENV=prod`.

## Changelog

`make changelog` builds the changelog from your commit messages, grouped by scope.

```sh
make changelog              # commits since the last tag
make changelog all          # every release
make changelog write        # into ./changelog/<major>.x.md, keeping your own notes
make changelog notes        # as a release body, ending in the compare link
make changelog write force  # rebuilds each section from git
```
