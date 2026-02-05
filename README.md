<h1 id="top">
  <a href="#top">
    <img src="https://raw.githubusercontent.com/brnshkr/config/refs/heads/master/.github/images/project-logo.png" alt="@brnshkr/config project logo" title="@brnshkr/config">
  </a>

  [![Semantic Versioning 2.0.0][semver-2.0.0-shield-url]][semver-2.0.0-url]
  [![MIT License][license-shield-url]][license-url]
  [![Stars][stars-shield-url]][stars-url]
  [![Forks][forks-shield-url]][forks-url]
  [![Issues][issues-shield-url]][issues-url]
</h1>

Centralized collection of configuration and tooling used across all [@brnshkr][@brnshkr-organization-url] projects.

_[☄️ Bug Reports / Feature Requests »][issues-url]_

<!-- omit in toc -->
## Table of Contents

<!-- NOTICE: All anchors must not include the emoji to work on github, the ❤️ and ⚙️ for some reason must be url encoded though -->
- [👋 About the Project](#-about-the-project)
- [☕ JS](#-js)
  - [🧰 Prerequisites](#-prerequisites)
  - [🚀 Installation](#-installation)
    - [✋ Manual](#-manual)
    - [🎨 Custom](#-custom)
  - [👀 Usage](#-usage)
  - [💻 Development](#-development)
- [🐘 PHP](#-php)
  - [🧰 Prerequisites](#-prerequisites-1)
  - [🚀 Installation](#-installation-1)
    - [🤖 Automatic](#-automatic)
    - [✋ Manual](#-manual-1)
    - [🎨 Custom](#-custom-1)
  - [👀 Usage](#-usage-1)
  - [💻 Development](#-development-1)
- [🔨 TODOs / Roadmap](#-todos--roadmap)
- [❤️ Contributing](#️-contributing)
  - [💄 Commit Style](#-commit-style)
  - [⚙️ Workflows](#️-workflows)
- [🔖 Versioning](#-versioning)
- [📃 License](#-license)
- [🌐 Acknowledgments](#-acknowledgments)

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## 👋 About the Project

**@brnshkr/config** is a centralized, opinionated collection of shared configuration files, tooling, and workflows for JavaScript and PHP projects. It helps standardizing linting, formatting, static analysis, and development workflows across repositories — reducing setup time, preventing config drift, and improving code quality and consistency.

> ❗ **Note** ❗  
> While you're more than welcome to use this in your own projects, the configurations are tailored specifically for the [@brnshkr][@brnshkr-organization-url] ecosystem and may not be a perfect fit elsewhere.

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## ☕ JS

### 🧰 Prerequisites

- Node.js >= v24 or Bun >= 1.3 (Older versions may work, but are untested)
- Any JavaScript package manager (Bun, Yarn, PNPM, NPM)

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

### 🚀 Installation

<!-- omit in toc -->
#### Bun

```sh
bun a -D @brnshkr/config
```

<!-- omit in toc -->
#### Yarn

```sh
yarn add -D @brnshkr/config
```

<!-- omit in toc -->
#### PNPM

```sh
pnpm add -D @brnshkr/config
```

<!-- omit in toc -->
#### NPM

```sh
npm i -D @brnshkr/config
```

This repository currently only provides one way to integrate configuration files (An automatic setup is planned, See [🔨 TODOs / Roadmap](#-todos--roadmap)):

- [**Manual setup**](#-manual) by copying the example configuration files yourself

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

#### ✋ Manual

Take a look at the `peerDependencies` in the [package.json](./package.json) file and install the ones you need for the modules you want to use.  
You can then copy the specific configs to your project:

<!-- omit in toc -->
##### TypeScript

```sh
cp -v ./node_modules/@brnshkr/config/conf/tsconfig.json.example ./tsconfig.json
```

<!-- omit in toc -->
##### ESLint

```sh
cp -v ./node_modules/@brnshkr/config/conf/eslint.config.mjs.example ./conf/eslint.config.mjs
```

<!-- omit in toc -->
##### Stylelint

```sh
cp -v ./node_modules/@brnshkr/config/conf/stylelint.config.mjs.example ./conf/stylelint.config.mjs
```

<!-- omit in toc -->
##### All

```sh
cp -v ./node_modules/@brnshkr/config/conf/tsconfig.json.example ./tsconfig.json \
  && cp -v ./node_modules/@brnshkr/config/conf/eslint.config.mjs.example ./conf/eslint.config.mjs \
  && cp -v ./node_modules/@brnshkr/config/conf/stylelint.config.mjs.example ./conf/stylelint.config.mjs
```

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

#### 🎨 Custom

Take a look at the function signatures for exact details.

<!-- omit in toc -->
##### ESLint

```js
// ./eslint.config.mjs

import { getConfig } from '@brnshkr/config/eslint';

export default getConfig(/* customize */);
```

<!-- omit in toc -->
##### Stylelint

```js
// ./stylelint.config.mjs

import { getConfig } from '@brnshkr/config/stylelint';

export default getConfig(/* customize */);
```

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

### 👀 Usage

<!-- omit in toc -->
#### Run Tooling

This package provides **configurations**, not a hard requirement on _how_ you run tools.  
A few possible ways are listed below:

<!-- omit in toc -->
##### Option 1 — Run Tools Directly (Most Flexible)

<!-- omit in toc -->
###### ESLint

Example call, adjust as needed

```sh
bun eslint --config ./conf/eslint.config.ts --cache --cache-location ./.cache/eslint.cache.json
```

<!-- omit in toc -->
###### Stylelint

Example call, adjust as needed

```sh
bun stylelint --config ./conf/stylelint.config.mjs --cache --cache-location ./.cache/stylelint.cache.json **/*.{css,ejs,html,less,postcss,scss,svelte,svg,vue}
```

<!-- omit in toc -->
##### Option 2 — Run Helper Scripts (Bun Only, @brnshkr Convention)

For these scripts to work you need to follow the convention of putting your configuration files into the [`./conf`](./conf) directory (Exactly how it is done in this project as well).

<!-- omit in toc -->
###### ESLint (TypeScript Only)

Expected configuration file: `./conf/eslint.config.ts`

```sh
bun ./node_modules/@brnshkr/config/dist/scripts/eslint.mjs
```

<!-- omit in toc -->
###### Stylelint

Expected configuration file: `./conf/stylelint.config.mjs`

```sh
bun ./node_modules/@brnshkr/config/dist/scripts/stylelint.mjs
```

<!-- omit in toc -->
#### IDE Setup

When using the recommended way of putting config files into the `./conf` directory it might be neccesary to instruct your IDE to read these files correctly.  
If you need a VSCode setup and have the specific [`extensions`](https://github.com/brnshkr/config/blob/master/.vscode/extensions.json) installed you can take a look at the `Project specific` section in [`./.vscode/settings.json`](https://github.com/brnshkr/config/blob/master/.vscode/settings.json).

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

### 💻 Development

<!-- omit in toc -->
#### Setup

Install dependencies and setup git hooks:

```sh
bun install \
  && bun install-hooks
```

<!-- omit in toc -->
#### Scripts

We recommend using the scripts provided in the [package.json](./package.json) file as the primary way of running common tasks.  
Have a look yourself for a full list of available targets.

<!-- omit in toc -->
##### Common targets

Here are some frequently used examples:

- `bun lint` — Run ESLint, Stylelint and Commitlint
- `bun inspect:eslint` — Inspect ESLint configuration
- `bun check` — Run TypeScript checks, linters and Vitest
- `bun run test` — Run Vitest test suite
- `bun test-update` — Run Vitest test suite and update snapshots
- `bun run build` — Build the project and generate types
- `bun watch` — Build the project in watch mode

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## 🐘 PHP

### 🧰 Prerequisites

- PHP >= 8.5 (Older versions may work, but are untested)
- Composer >= 2.9 (Older versions may work, but are untested)
- PHP Extensions:
  - `json`
  - `mbstring`

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

### 🚀 Installation

<!-- omit in toc -->
#### Composer

```sh
composer req --dev brnshkr/config
```

This repository provides two ways to integrate configuration files and setup tools into your project:

- [**Automatic setup**](#-automatic) via the Composer plugin
- [**Manual setup**](#-manual-1) by copying the example configuration files yourself

#### 🤖 Automatic

If you allow this package to run as a Composer plugin (Composer will prompt you on first install), several helper commands become available.  
The most commonly used is the automatic setup command which installs packages for selected modules, copies example config files into your repository, and can optionally create a `Makefile` and/or a `.gitignore` file.

Run the automatic setup with defaults:

```sh
composer brnshkr:config:setup
```

Run the automatic setup with all flags enabled:

```sh
composer brnshkr:config:setup -gofacme
```

Take a look at the [plugin commands](#plugin-commands) section to see a full list of available commands.

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

#### ✋ Manual

Take a look at the `suggest`ed packages in the [composer.json](./composer.json) file and install the ones you need for the modules you want to use.  
You can then copy the specific configs to your project:

<!-- omit in toc -->
##### PHP CS Fixer

```sh
cp -v ./vendor/brnshkr/config/conf/.php-cs-fixer.php.example ./conf/.php-cs-fixer.php \
  && cp -v ./vendor/brnshkr/config/conf/.php-cs-fixer.dist.php.example ./conf/.php-cs-fixer.dist.php
```

<!-- omit in toc -->
##### Rector

```sh
cp -v ./vendor/brnshkr/config/conf/rector.php.example ./conf/rector.php \
  && cp -v ./vendor/brnshkr/config/conf/rector.dist.php.example ./conf/rector.dist.php
```

<!-- omit in toc -->
##### PHPStan

```sh
cp -v ./vendor/brnshkr/config/conf/phpstan.neon.example ./conf/phpstan.neon \
  && cp -v ./vendor/brnshkr/config/conf/phpstan.dist.neon.example ./conf/phpstan.dist.neon
```

<!-- omit in toc -->
##### Makefile

```sh
cp -v ./vendor/brnshkr/config/conf/Makefile.example ./Makefile
```

<!-- omit in toc -->
##### Gitignore

```sh
cp -v ./vendor/brnshkr/config/conf/.gitignore.example ./.gitignore
```

<!-- omit in toc -->
##### All

```sh
cp -v ./vendor/brnshkr/config/conf/.php-cs-fixer.php.example ./conf/.php-cs-fixer.php \
  && cp -v ./vendor/brnshkr/config/conf/.php-cs-fixer.dist.php.example ./conf/.php-cs-fixer.dist.php \
  && cp -v ./vendor/brnshkr/config/conf/rector.php.example ./conf/rector.php \
  && cp -v ./vendor/brnshkr/config/conf/rector.dist.php.example ./conf/rector.dist.php \
  && cp -v ./vendor/brnshkr/config/conf/phpstan.neon.example ./conf/phpstan.neon \
  && cp -v ./vendor/brnshkr/config/conf/phpstan.dist.neon.example ./conf/phpstan.dist.neon \
  && cp -v ./vendor/brnshkr/config/conf/Makefile.example ./Makefile \
  && cp -v ./vendor/brnshkr/config/conf/.gitignore.example ./.gitignore
```

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

#### 🎨 Custom

Take a look at the function signatures for exact details.

<!-- omit in toc -->
##### PHP CS Fixer

```php
// ./.php-cs-fixer.dist.php

<?php

declare(strict_types=1);

use Brnshkr\Config\PhpCsFixerConfig;

return PhpCsFixerConfig::get(/* customize */);
```

<!-- omit in toc -->
##### Rector

```php
// ./rector.php

<?php

declare(strict_types=1);

use Brnshkr\Config\RectorConfig;

return RectorConfig::get(/* customize */);
```

<!-- omit in toc -->
##### PHPStan

```yaml
# phpstan.dist.neon

includes:
  - '%currentWorkingDirectory%/vendor/brnshkr/config/conf/phpstan.dist.neon'

# customize
```

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

### 👀 Usage

<!-- omit in toc -->
#### Run Tooling

This package provides **configurations**, not a hard requirement on _how_ you run tools.  
A few possible ways are listed below:

<!-- omit in toc -->
##### Option 1 — Run Tools Directly (Most Flexible)

<!-- omit in toc -->
###### PHP CS Fixer

Example call, adjust as needed

```sh
php ./vendor/bin/php-cs-fixer fix -v --show-progress=dots --config ./conf/.php-cs-fixer.php
```

<!-- omit in toc -->
###### Rector

Example call, adjust as needed

```sh
php ./vendor/bin/rector process --config ./conf/rector.php
```

<!-- omit in toc -->
###### PHPStan

Example call, adjust as needed

```sh
php ./vendor/bin/phpstan analyze --memory-limit=-1 --configuration ./conf/phpstan.neon
```

<!-- omit in toc -->
##### Option 2 — Run Helper Scripts (Make Only, @brnshkr Convention)

For these scripts to work you need to follow the convention of putting your configuration files into the [`./conf`](./conf) directory (Exactly how it is done in this project as well).

Do not forget to setup your Makefile with this projects Makefile as a base:

```Makefile
include ./vendor/brnshkr/config/conf/Makefile
```

<!-- omit in toc -->
###### PHP CS Fixer

Expected configuration file: `./conf/.php-cs-fixer.php`

```sh
make php-cs-fixer
```

<!-- omit in toc -->
###### Rector

Expected configuration file: `./conf/rector.php`

```sh
make rector
```

<!-- omit in toc -->
###### PHPStan

Expected configuration file: `./conf/phpstan.neon`

```sh
make phpstan
```

<!-- omit in toc -->
#### IDE Setup

When using the recommended way of putting config files into the `./conf` directory it might be neccesary to instruct your IDE to read these files correctly.  
If you need a VSCode setup and have the specific [`extensions`](https://github.com/brnshkr/config/blob/master/.vscode/extensions.json) installed you can take a look at the `Project specific` section in [`./.vscode/settings.json`](https://github.com/brnshkr/config/blob/master/.vscode/settings.json).

<!-- omit in toc -->
#### Plugin Commands

Overview of all commands provided by the composer plugin.  
For full usage run `composer help <command>`, `composer <command> --help` or `composer <command> -h`.

| Command | Alias | Description |
| --- | --- | --- |
| `brnshkr:config` | `b:c` | Displays the plugin overview and a list of available commands. Useful to quickly discover what the plugin exposes. |
| `brnshkr:config:setup [<modules>...]` | `b:c:s` | Interactive setup helper: installs suggested packages for modules, copies example config files, and can create a `Makefile` and/or a `.gitignore` file. |
| `brnshkr:config:update-php-extensions` | `b:c:upe` | Scans installed packages and updates `composer.json` with required `ext-*` platform packages. |
| `brnshkr:config:extract-phar <package>` | `b:c:ep` | Extracts a `.phar` file from a given vendor package. |

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

### 💻 Development

<!-- omit in toc -->
#### Setup

Install dependencies and setup project tooling with the following commands and adjust as needed:

```sh
composer install \
  && cp -v ./conf/.php-cs-fixer.php.example ./conf/.php-cs-fixer.php \
  && cp -v ./conf/rector.php.example ./conf/rector.php \
  && cp -v ./conf/phpstan.neon.example ./conf/phpstan.neon
```

<!-- omit in toc -->
#### Make

We recommend using [GNU Make][make-url] as the primary task runner.  
See [the Makefile](./conf/Makefile) for a full list of available targets.  
You can also run `make help` or simply `make` to view all targets with brief descriptions.

If you need local overrides, create a `./.local/Makefile` — the main Makefile automatically includes it if present.

<!-- omit in toc -->
##### Common targets

Here are some frequently used examples (see `make help` for the complete list):

- `make help` — Show available targets and usage
- `make rector` — Run Rector to apply automated PHP refactorings
- `make php-cs-fixer` — Run PHP-CS-Fixer to format and fix coding-style issues
- `make phpstan` — Run PHPStan static analysis
- `make test` — Run PHPUnit test suite
- `make test-update` — Run PHPUnit test suite and update snapshots
- `make check` — Run Rector, PHP-CS-Fixer, PHPStan and PHPUnit

## 🔨 TODOs / Roadmap

- Add setup command for JS package (like `composer brnshkr:config:setup`)
- Expand [`⚙️ Worflows`](#️-workflows) section in readme
- Write sections about custom PHPStan and ESLint rules
- Add all around support for enforcing TypeScript aliases with ESLint
- Add Vue support
- Add React support
- Add Tailwind support via <https://github.com/schoero/eslint-plugin-better-tailwindcss>
- Improve test setup

Any help is always greatly appreciated 🙂

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## ❤️ Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the tag "enhancement".
Don't forget to give the project a star! Thanks again!

1. Fork the project
2. Create your feature branch => `git checkout -b feature/my-new-feature`
3. Commit your changes => `git commit -m 'feat(my-new-feature): add some awesome new feature'`
4. Push to the branch => `git push origin feature/my-new-feature`
5. Open a pull request

### 💄 Commit Style

This project mostly follows the [Conventional Commits](https://www.conventionalcommits.org) specification.  
There are only a few differences. The main one is that the scope is required:  
So **instead of** this commit message signature: `<type>[optional scope]: <description>`  
You **should use** this one: `<type><scope>: <description>`  
Further details can be found in the [Commitlint configuration](https://github.com/brnshkr/config/blob/master/conf/commitlint.config.mjs).

### ⚙️ Workflows

See [./.github/workflows](https://github.com/brnshkr/config/blob/master/.github/workflows) for more information.

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## 🔖 Versioning

This project follows [Semantic Versioning 2.0.0][semver-2.0.0-url].  
The NPM and Composer packages are versioned in sync, so a version change does not necessarily indicate a change in a specific package.  
Also please note the following additional information:

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
- [Stylelint](https://github.com/stylelint/stylelint)
- [Commitlint](https://github.com/conventional-changelog/commitlint)
- [@antfu/eslint-config](https://github.com/antfu/eslint-config)
- [PHP](https://www.php.net)
- [PHPStan](https://github.com/phpstan/phpstan)
- [Rector](https://github.com/rectorphp/rector)
- [GNU Make](https://www.gnu.org/software/make)
- [PHP Coding Standards Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer)
- [Best-README-Template](https://github.com/othneildrew/Best-README-Template) by [othneildrew](https://github.com/othneildrew)
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

[make-url]: https://www.gnu.org/software/make

[semver-2.0.0-url]: https://semver.org/#semantic-versioning-200
[semver-2.0.0-shield-url]: https://img.shields.io/badge/semver-2.0.0-blue?label=🔖%20semver&style=flat-square&labelColor=%237f399d&color=%23a5097e

[license-url]: #-license
[license-shield-url]: https://img.shields.io/github/license/brnshkr/config.svg?label=📃%20license&style=flat-square&labelColor=%237f399d&color=%23a5097e

[stars-url]: https://github.com/brnshkr/config/stargazers
[stars-shield-url]: https://img.shields.io/github/stars/brnshkr/config.svg?label=⭐%20stars&style=flat-square&labelColor=%237f399d&color=%23a5097e

[forks-url]: https://github.com/brnshkr/config/network/members
[forks-shield-url]: https://img.shields.io/github/forks/brnshkr/config.svg?label=🍴%20forks&style=flat-square&labelColor=%237f399d&color=%23a5097e

[issues-url]: https://github.com/brnshkr/config/issues
[issues-shield-url]: https://img.shields.io/github/issues/brnshkr/config.svg?label=🚨%20issues&style=flat-square&labelColor=%237f399d&color=%23a5097e
