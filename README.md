<h1 id="top">
  <a href="#top">
    <img
      src="https://raw.githubusercontent.com/brnshkr/brand/refs/heads/master/images/projects/config.png"
      alt="@brnshkr/config project logo"
      title="@brnshkr/config"
    >
  </a>

  [![Semantic Versioning 2.0.0][semver-2.0.0-shield-url]][semver-2.0.0-url]
  [![MIT License][license-shield-url]][license-url]
  [![Stars][stars-shield-url]][stars-url]
  [![Forks][forks-shield-url]][forks-url]
  [![Issues][issues-shield-url]][issues-url]
  [![Release][release-shield-url]][release-url]
</h1>

Centralized collection of configuration and tooling used across all [@brnshkr][@brnshkr-organization-url] projects.

_[☄️ Bug Reports / Feature Requests »][issues-url]_

<!-- omit in toc -->
## Table of Contents

<!-- 
  NOTICE:
  GitHub strips emojis in anchors, but multi-codepoint characters may leave invisible remnants,
  causing anchors to differ and require URL encoding.
-->
- [👋 About the Project](#-about-the-project)
- [📚 Documentation](#-documentation)
- [🧰 Prerequisites](#-prerequisites)
- [🚀 Installation](#-installation)
- [👀 Usage](#-usage)
- [🧩 Custom Rules](#-custom-rules)
- [❤️ Contributing](#️-contributing)
  - [💄 Commit Style](#-commit-style)
  - [⚙️ Workflows](#️-workflows)
- [🔖 Versioning](#-versioning)
- [📃 License](#-license)
- [🌐 Acknowledgments](#-acknowledgments)

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## 👋 About the Project

**@brnshkr/config** is a centralized, opinionated collection of shared configuration files,
tooling, and workflows for JavaScript and PHP projects.
It helps standardizing linting, formatting, static analysis, and development workflows across repositories
— reducing setup time, preventing config drift, and improving code quality and consistency.

> ❗ **Note** ❗  
> While you're more than welcome to use this in your own projects, the configurations are tailored specifically
> for the [@brnshkr][@brnshkr-organization-url] ecosystem and may not be a perfect fit elsewhere.

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## 📚 Documentation

This README covers installation and usage. The full reference — custom rules, config builders, the Composer plugin,
the shared [`Makefile`](https://github.com/brnshkr/config/blob/master/docs/Makefile.md), and the development setup
— lives in [`./docs`](https://github.com/brnshkr/config/blob/master/docs), organized by stack and tool.

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## 🧰 Prerequisites

<!-- omit in toc -->
### JS

- Node.js >= v24.15 or Bun >= v1.4 (Older versions may work, but are untested)
- Any JavaScript package manager (Bun, Yarn, PNPM, NPM)

<!-- omit in toc -->
### PHP

- PHP >= 8.5 (Older versions may work, but are untested)
- Composer >= 2.9 (Older versions may work, but are untested)
- PHP Extensions:
  - `json`
  - `mbstring`

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## 🚀 Installation

<!-- omit in toc -->
### Bun

```sh
bun a -D -E @brnshkr/config
```

Any other JavaScript package manager works the same way,
e.g. `yarn add -D -E @brnshkr/config`, `pnpm add -D -E @brnshkr/config` or `npm i -D -E @brnshkr/config`.

Take a look at the `peerDependencies` in the [package.json](./package.json) file
and install the ones you need for the modules you want to use.

<!-- omit in toc -->
### Composer

```sh
composer r --dev brnshkr/config
```

Take a look at the `suggest`ed packages in the [composer.json](./composer.json) file and install the ones
you need for the modules you want to use.  
To have the packages installed for you, pick the modules interactively:

```sh
composer brnshkr:config:setup
```

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## 👀 Usage

This package provides **configurations**, not a hard requirement on _how_ you run tools.

<!-- omit in toc -->
### 🎨 Configuration

Each tool reads a config file like the ones below. Put it wherever the tool looks for one,
or anywhere else and point the tool to it with its `--config` option; @brnshkr projects keep them in `./conf`.

To use the defaults as they are, re-export them:

```js
// ./conf/eslint.dist.mjs

export { default } from '@brnshkr/config/eslint';
```

```php
// ./conf/phpstan.dist.php

return include __DIR__ . '/../vendor/brnshkr/config/conf/phpstan.dist.php';
```

To customize them, pass your options to `getConfig()`.
Take a look at the function signatures for exact details.

```js
// ./conf/eslint.dist.mjs

import { getConfig } from '@brnshkr/config/eslint';

export default getConfig(/* customize */);
```

```php
// ./conf/phpstan.dist.php

use Brnshkr\Config\PhpStan;

return PhpStan::getConfig(/* customize */);
```

The other config builders work the same way, with their module listed in the table below.
PHPStan reads a `.neon` file by default and a `.php` one with `--configuration`, and both can include ours:

```php
// ./conf/phpstan.dist.php

return [
    'includes' => [
        __DIR__ . '/../vendor/brnshkr/config/conf/phpstan.dist.php',
    ],
];
```

Every tool and its options are described in the [docs](https://github.com/brnshkr/config/blob/master/docs/index.md).

<!-- omit in toc -->
### Run Tooling

A few possible ways are listed below:

<!-- omit in toc -->
#### Option 1 — Run Tools Directly (Most Flexible)

Example calls, adjust as needed (these are the commands the make targets run)

```sh
# ESLint
bun --bun x eslint --config ./conf/eslint.dist.mjs --cache --cache-location ./.cache/eslint.cache.json --max-warnings 0 --fix

# Stylelint
bun --bun x stylelint --config ./conf/stylelint.dist.mjs --config-basedir ./ --cache --cache-location ./.cache/stylelint.cache.json --allow-empty-input --max-warnings 0 --ignore-path ./.gitignore --ignore-path ./.stylelintignore --fix './**/*.{css,less,scss,ejs,html,svelte,svg,vue,astro,xml}'

# markdownlint
bun --bun x markdownlint-cli2 --config ./conf/markdownlint.dist.mjs --fix './**/*.{md,markdown}'

# commitlint
bun --bun x commitlint --config ./conf/commitlint.dist.mjs --edit

# TypeScript
bun --bun x tsc --project ./tsconfig.json --noEmit

# Vitest
bun --bun x vitest --config ./conf/vitest.dist.mjs --run --passWithNoTests
```

```sh
# PHP CS Fixer
./vendor/bin/php-cs-fixer fix --config ./conf/php-cs-fixer.dist.php --show-progress=dots -v

# Rector
./vendor/bin/rector process --config ./conf/rector.dist.php --memory-limit=-1

# PHPStan
./vendor/bin/phpstan analyze --configuration ./conf/phpstan.dist.php --memory-limit=-1 -vv

# Twig CS Fixer
./vendor/bin/twig-cs-fixer lint --fix --config ./conf/twig-cs-fixer.dist.php -v

# PHPUnit
./vendor/bin/phpunit --configuration ./conf/phpunit.dist.xml
```

```sh
# semgrep
semgrep scan --config p/default --config ./conf/semgrep.dist.yaml --error --quiet --metrics off --disable-version-check --no-rewrite-rule-ids

# hadolint
hadolint --config ./conf/hadolint.dist.yaml ./Dockerfile

# actionlint
actionlint -config-file ./conf/actionlint.dist.yaml
```

<!-- omit in toc -->
#### Option 2 — Run Make Targets (@brnshkr Convention)

Your own Makefile includes this one. Copy the starter rather than writing the include by hand: it guards the
include, so a fresh clone can run a plain `make` before anything is installed.

```sh
cp -v ./node_modules/@brnshkr/config/conf/Makefile.dist ./Makefile \
  && make startup
```

When installed through Composer, the starter lives in `./vendor/brnshkr/config/conf/Makefile.dist` instead.  
A target appears once the tool it runs is installed, so `make help` lists what your repository actually has,
`make ci` runs all of it, and `make startup` writes the `.gitignore`, `.gitattributes` and editor files you are still missing.
The full reference is [`./docs/Makefile.md`](https://github.com/brnshkr/config/blob/master/docs/Makefile.md).

These targets look for your configuration files in the `./conf` directory
(exactly how it is done in this project as well; see [`./conf`](https://github.com/brnshkr/config/blob/master/conf))
and use the defaults of this package without one.

| Tool | Module | Target | Optional configuration file |
| --- | --- | --- | --- |
| ESLint | `@brnshkr/config/eslint` | `make eslint` | `./conf/eslint.dist.mjs` |
| Stylelint | `@brnshkr/config/stylelint` | `make stylelint` | `./conf/stylelint.dist.mjs` |
| markdownlint | `@brnshkr/config/markdownlint` | `make markdownlint` | `./conf/markdownlint.dist.mjs` |
| commitlint | `@brnshkr/config/commitlint` | `make commitlint` | `./conf/commitlint.dist.mjs` |
| TypeScript | `@brnshkr/config/conf/tsconfig.json` | `make typescript` | `./tsconfig.json` |
| Vitest | `@brnshkr/config/vitest` | `make vitest` | `./conf/vitest.dist.mjs` |
| PHP CS Fixer | `Brnshkr\Config\PhpCsFixer` | `make php-cs-fixer` | `./conf/php-cs-fixer.dist.php` |
| Rector | `Brnshkr\Config\Rector` | `make rector` | `./conf/rector.dist.php` |
| PHPStan | `Brnshkr\Config\PhpStan` | `make phpstan` | `./conf/phpstan.dist.php` |
| Twig CS Fixer | `Brnshkr\Config\TwigCsFixer` | `make twig-cs-fixer` | `./conf/twig-cs-fixer.dist.php` |
| PHPUnit | — | `make phpunit` | `./conf/phpunit.dist.xml` |
| semgrep | — | `make semgrep` | `./conf/semgrep.dist.yaml` |
| hadolint | — | `make hadolint` | `./conf/hadolint.dist.yaml` |
| actionlint | — | `make actionlint` | `./conf/actionlint.dist.yaml` |

When using the `./conf` directory it might be necessary to instruct your IDE to read these files correctly.  
If you need a VS Code setup and have the specific [`extensions`](https://github.com/brnshkr/config/blob/master/.vscode/extensions.json)
installed, `make configs` writes the settings they need into `./.vscode/settings.json`,
pointing at the files the targets use.

<!-- omit in toc -->
### Composer Plugin

The Composer package also adds a few helper commands,
see the [Composer plugin docs](https://github.com/brnshkr/config/blob/master/docs/php/Composer.md).

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## 🧩 Custom Rules

Both stacks ship custom rules on top of the upstream ones. Each rule is documented with examples
in the [Custom ESLint Rules docs](https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/index.md)
and the [Custom PHPStan Rules docs](https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/index.md).

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## ❤️ Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create.
Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request.
You can also simply open an issue with the tag "enhancement".
Don't forget to give the project a star! Thanks again!

1. Fork the project
2. Create your feature branch => `git checkout -b feature/my-new-feature`
3. Commit your changes => `git commit -m 'feat(my-new-feature): add some awesome new feature'`
4. Push to the branch => `git push origin feature/my-new-feature`
5. Open a pull request

New to the codebase?
The [Development docs](https://github.com/brnshkr/config/blob/master/docs/development.md)
cover environment setup and the day-to-day commands for both stacks.

Any help is always greatly appreciated 🙂

### 💄 Commit Style

This project mostly follows the [Conventional Commits](https://www.conventionalcommits.org) specification.  
There are only a few differences. The main one is that the scope is required:  
So **instead of** this commit message signature: `<type>[optional scope]: <description>`  
You **should use** this one: `<type><scope>: <description>`  
Further details can be found in the [commitlint configuration](https://github.com/brnshkr/config/blob/master/conf/commitlint.mjs).

### ⚙️ Workflows

See [./.github/workflows](https://github.com/brnshkr/config/blob/master/.github/workflows) for more information.

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## 🔖 Versioning

This project follows [Semantic Versioning 2.0.0][semver-2.0.0-url].  
The NPM and Composer packages are versioned in sync,
so a version change does not necessarily indicate a change in a specific package.  

> ❗ **Note** ❗  
> Since changes to rules and dependencies are not considered breaking,
> even a patch release may introduce new errors in code that hasn't changed and break your CI without notice.
> We therefore strongly recommend pinning to an exact version
> (`-E` for the JS package managers, `composer r --dev brnshkr/config:X.Y.Z` for Composer)
> so updates stay opt-in and can be applied on your own schedule.

<!-- omit in toc -->
### Changes Considered as Breaking Changes

- Version requirement changes of Node.js, Bun, PHP or Composer
- Changes that might break existing userland configs

<!-- omit in toc -->
### Changes Considered as Non-Breaking Changes

- Changes regarding used rules and their options
- Version updates, introduction or removal of dependencies
- Updates of minimum required versions of optional dependencies

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## 📃 License

Distributed under the MIT License. See [LICENSE](./LICENSE) for more information.

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## 🌐 Acknowledgments

- [TypeScript](https://www.typescriptlang.org)
- [ESLint](https://github.com/eslint/eslint)
- [markdownlint-cli2](https://github.com/DavidAnson/markdownlint-cli2)
- [Stylelint](https://github.com/stylelint/stylelint)
- [commitlint](https://github.com/conventional-changelog/commitlint)
- [@antfu/eslint-config](https://github.com/antfu/eslint-config)
- [PHP](https://www.php.net)
- [PHPStan](https://github.com/phpstan/phpstan)
- [Rector](https://github.com/rectorphp/rector)
- [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer)
- [Twig-CS-Fixer](https://github.com/VincentLanglet/Twig-CS-Fixer)
- [GNU Make](https://www.gnu.org/software/make)
- [Best-README-Template](https://github.com/othneildrew/Best-README-Template)
- [Choose an Open Source License](https://choosealicense.com)
- [Shields.io](https://shields.io)
- <a href="https://github.com/brnshkr">
    <img src="https://avatars.githubusercontent.com/u/180693849" width="24" align="center" alt="@brnshkr organization logo">
    @brnshkr organization
  </a>

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

<!-- END OF CONTENT -->

<!-- MARKDOWN LINKS & IMAGES -->
<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->

[@brnshkr-organization-url]: https://github.com/brnshkr

[semver-2.0.0-url]: https://semver.org/#semantic-versioning-200
[semver-2.0.0-shield-url]: https://img.shields.io/badge/semver-2.0.0-blue?label=🔖%20semver&style=flat-square&labelColor=%237f399d&color=%23a5097e

[license-url]: https://github.com/brnshkr/config/blob/master/LICENSE
[license-shield-url]: https://img.shields.io/github/license/brnshkr/config.svg?label=📃%20license&style=flat-square&labelColor=%237f399d&color=%23a5097e

[stars-url]: https://github.com/brnshkr/config/stargazers
[stars-shield-url]: https://img.shields.io/github/stars/brnshkr/config.svg?label=⭐%20stars&style=flat-square&labelColor=%237f399d&color=%23a5097e

[forks-url]: https://github.com/brnshkr/config/network/members
[forks-shield-url]: https://img.shields.io/github/forks/brnshkr/config.svg?label=🍴%20forks&style=flat-square&labelColor=%237f399d&color=%23a5097e

[issues-url]: https://github.com/brnshkr/config/issues
[issues-shield-url]: https://img.shields.io/github/issues/brnshkr/config.svg?label=🚨%20issues&style=flat-square&labelColor=%237f399d&color=%23a5097e

<!-- NOTICE: Only use packagist version here since both packages are versioned in sync -->
[release-url]: https://github.com/brnshkr/config/releases
[release-shield-url]: https://img.shields.io/packagist/v/brnshkr/config?style=flat-square&label=📦%20npm%2Fpackagist&labelColor=7f399d&color=%23a5097e
