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
- [☕ JS](#-js)
  - [🧰 Prerequisites](#-prerequisites)
  - [🚀 Installation](#-installation)
    - [🎨 Custom](#-custom)
  - [👀 Usage](#-usage)
  - [🧩 Custom ESLint Rules](#-custom-eslint-rules)
- [🐘 PHP](#-php)
  - [🧰 Prerequisites](#-prerequisites-1)
  - [🚀 Installation](#-installation-1)
    - [🎨 Custom](#-custom-1)
  - [👀 Usage](#-usage-1)
  - [🧩 Custom PHPStan Rules](#-custom-phpstan-rules)
- [🔨 TODOs / Roadmap](#-todos--roadmap)
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

## ☕ JS

### 🧰 Prerequisites

- Node.js >= v24 or Bun >= v1.3 (Older versions may work, but are untested)
- Any JavaScript package manager (Bun, Yarn, PNPM, NPM)

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

### 🚀 Installation

<!-- omit in toc -->
#### Bun

```sh
bun a -D -E @brnshkr/config
```

<!-- omit in toc -->
#### Yarn

```sh
yarn add -D -E @brnshkr/config
```

<!-- omit in toc -->
#### PNPM

```sh
pnpm add -D -E @brnshkr/config
```

<!-- omit in toc -->
#### NPM

```sh
npm i -D -E @brnshkr/config
```

Take a look at the `peerDependencies` in the [package.json](./package.json) file
and install the ones you need for the modules you want to use.  
Copy the starter `Makefile` once, and let it write the rest:

```sh
cp -v ./node_modules/@brnshkr/config/conf/Makefile.dist ./Makefile \
  && make startup
```

`make startup` installs each stack, writes every config and `.gitignore` the project is missing, and never
touches a file that is already there.

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

#### 🎨 Custom

Take a look at the function signatures for exact details.

<!-- omit in toc -->
##### ESLint

```js
// ./conf/eslint.mjs

import { getConfig } from '@brnshkr/config/eslint';

export default getConfig(/* customize */);
```

<!-- omit in toc -->
##### Stylelint

```js
// ./conf/stylelint.mjs

import { getConfig } from '@brnshkr/config/stylelint';

export default getConfig(/* customize */);
```

<!-- omit in toc -->
##### markdownlint

```js
// ./conf/markdownlint.mjs

import { getConfig } from '@brnshkr/config/markdownlint';

export default getConfig(/* customize */);
```

<!-- omit in toc -->
##### commitlint

```js
// ./conf/commitlint.mjs

import { getConfig } from '@brnshkr/config/commitlint';

export default getConfig(/* customize */);
```

<!-- omit in toc -->
##### Vitest

```js
// ./conf/vitest.mjs

import { getConfig } from '@brnshkr/config/vitest';

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
bun eslint --config ./conf/eslint.mjs --cache --cache-location ./.cache/eslint.cache.json --max-warnings 0
```

<!-- omit in toc -->
###### Stylelint

Example call, adjust as needed

```sh
bun stylelint --config ./conf/stylelint.mjs --config-basedir ./ --cache --cache-location ./.cache/stylelint.cache.json --max-warnings 0 "**/*.{css,ejs,html,less,postcss,scss,svelte,svg,vue}"
```

<!-- omit in toc -->
###### markdownlint

Example call, adjust as needed

```sh
bun markdownlint-cli2 --config ./conf/markdownlint.mjs "**/*.md"
```

<!-- omit in toc -->
###### commitlint

Example call, adjust as needed

```sh
bun commitlint --config ./conf/commitlint.mjs --edit
```

<!-- omit in toc -->
##### Option 2 — Run Make Targets (@brnshkr Convention)

For these targets to work you need to follow the convention of putting your configuration files into the `./conf` directory
(Exactly how it is done in this project as well; see [`./conf`](https://github.com/brnshkr/config/blob/master/conf)).

Your own Makefile includes this one. Copy the starter rather than writing the include by hand: it guards the
include, so a fresh clone can `make bootstrap` before anything is installed.

```sh
cp -v ./node_modules/@brnshkr/config/conf/Makefile.dist ./Makefile
```

A target appears once the tool it runs is installed, so `make help` lists what your repository actually has,
`make check` runs all of it, and `make startup` writes any config you are still missing.
The full reference is [`docs/Makefile.md`](https://github.com/brnshkr/config/blob/master/docs/Makefile.md).

<!-- omit in toc -->
###### ESLint (TypeScript Only)

Expected configuration file: `./conf/eslint.mjs`

```sh
make eslint
```

<!-- omit in toc -->
###### Stylelint

Expected configuration file: `./conf/stylelint.mjs`

```sh
make stylelint
```

<!-- omit in toc -->
###### markdownlint

Expected configuration file: `./conf/markdownlint.mjs`

```sh
make markdownlint
```

<!-- omit in toc -->
###### commitlint

Expected configuration file: `./conf/commitlint.mjs`

```sh
make commitlint
```

<!-- omit in toc -->
#### IDE Setup

When using the recommended way of putting config files into the `./conf` directory
it might be necessary to instruct your IDE to read these files correctly.  
If you need a VS Code setup and have the specific [`extensions`](https://github.com/brnshkr/config/blob/master/.vscode/extensions.json)
installed you can take a look at the `Project specific` section in [`./.vscode/settings.json`](https://github.com/brnshkr/config/blob/master/.vscode/settings.json).

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

### 🧩 Custom ESLint Rules

The default ESLint configuration ships a small `brnshkr` plugin that contributes a handful
of project-specific rules, all enabled out of the box. Each rule is documented with examples
in the [Custom ESLint Rules docs](https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/index.md).

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
composer r --dev brnshkr/config
```

Take a look at the `suggest`ed packages in the [composer.json](./composer.json) file and install the ones
you need for the modules you want to use.  
Copy the starter `Makefile` once, and let it write the rest:

```sh
cp -v ./vendor/brnshkr/config/conf/Makefile.dist ./Makefile \
  && make startup
```

`make startup` installs each stack, writes every config and `.gitignore` the project is missing, and never
touches a file that is already there.

To have the packages installed for you, pick the modules interactively:

```sh
composer brnshkr:config:setup
```

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

#### 🎨 Custom

Take a look at the function signatures for exact details.

<!-- omit in toc -->
##### PHP CS Fixer

```php
<?php
// ./conf/php-cs-fixer.dist.php

declare(strict_types=1);

use Brnshkr\Config\PhpCsFixer;

return PhpCsFixer::getConfig(/* customize */);
```

<!-- omit in toc -->
##### Rector

```php
<?php
// ./conf/rector.dist.php

declare(strict_types=1);

use Brnshkr\Config\Rector;

return Rector::getConfig(/* customize */);
```

<!-- omit in toc -->
##### PHPStan

```php
<?php
// ./conf/phpstan.dist.php

declare(strict_types=1);

use Brnshkr\Config\PhpStan;

return PhpStan::getConfig(/* customize */);
```

<!-- omit in toc -->
##### Twig CS Fixer

```php
<?php
// ./conf/twig-cs-fixer.dist.php

declare(strict_types=1);

use Brnshkr\Config\TwigCsFixer;

return TwigCsFixer::getConfig(/* customize */);
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
php ./vendor/bin/php-cs-fixer fix --config ./conf/php-cs-fixer.php -v --show-progress=dots
```

<!-- omit in toc -->
###### Rector

Example call, adjust as needed

```sh
php ./vendor/bin/rector process --config ./conf/rector.php --memory-limit=-1
```

<!-- omit in toc -->
###### PHPStan

Example call, adjust as needed

```sh
php ./vendor/bin/phpstan analyze --configuration ./conf/phpstan.php -vv --memory-limit=-1
```

<!-- omit in toc -->
###### Twig CS Fixer

Example call, adjust as needed

```sh
php ./vendor/bin/twig-cs-fixer fix --config ./conf/twig-cs-fixer.php -v
```

<!-- omit in toc -->
##### Option 2 — Run Make Targets (@brnshkr Convention)

For these targets to work you need to follow the convention of putting your configuration files into the `./conf` directory
(Exactly how it is done in this project as well; see [`./conf`](https://github.com/brnshkr/config/blob/master/conf)).

Your own Makefile includes this one. Copy the shipped starter rather than writing the include by hand:
it guards the include, so a fresh clone can `make bootstrap` before anything is installed.

```sh
cp -v ./vendor/brnshkr/config/conf/Makefile.dist ./Makefile
```

A target appears once the tool it runs is installed, so `make help` lists what your repository actually has,
`make check` runs all of it, and `make startup` writes any config you are still missing.
The full reference is [`docs/Makefile.md`](https://github.com/brnshkr/config/blob/master/docs/Makefile.md).

<!-- omit in toc -->
###### PHP CS Fixer

Expected configuration file: `./conf/php-cs-fixer.php`

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

Expected configuration file: `./conf/phpstan.php`

```sh
make phpstan
```

<!-- omit in toc -->
###### Twig CS Fixer

Expected configuration file: `./conf/twig-cs-fixer.php`

```sh
make twig-cs-fixer
```

<!-- omit in toc -->
#### IDE Setup

When using the recommended way of putting config files into the `./conf` directory
it might be necessary to instruct your IDE to read these files correctly.  
If you need a VS Code setup and have the specific [`extensions`](https://github.com/brnshkr/config/blob/master/.vscode/extensions.json)
installed you can take a look at the `Project specific` section in [`./.vscode/settings.json`](https://github.com/brnshkr/config/blob/master/.vscode/settings.json).

<!-- omit in toc -->
#### Plugin Commands

Overview of all commands provided by the composer plugin.  
For full usage run `composer help <command>`, `composer <command> --help` or `composer <command> -h`.

| Command | Alias | Description |
| --- | --- | --- |
| `brnshkr:config` | `b:c` | Displays the plugin overview and a list of available commands. Useful to quickly discover what the plugin exposes. |
| `brnshkr:config:update-php-extensions` | `b:c:upe` | Scans installed packages and updates `composer.json` with required `ext-*` platform packages. |
| `brnshkr:config:extract-phar <package>` | `b:c:ep` | Extracts a `.phar` file from a given vendor package. |

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

### 🧩 Custom PHPStan Rules

Beyond the upstream rule set, the default configuration ships a number of custom PHPStan rules in two flavors.
**Standalone rules** are general-purpose checks enabled out of the box,
while **architecture presets** are opinionated bundles of class-placement and isolation rules tailored to a
specific framework or architecture style — opt-in and configured through `setArchitecture()`.
Both are documented with examples in the [Custom PHPStan Rules docs](https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/index.md).

<p align="right"><a href="#top" title="Back to top">&nbsp;&nbsp;&nbsp;⬆&nbsp;&nbsp;&nbsp;</a></p>

## 🔨 TODOs / Roadmap

- Expand [`⚙️ Workflows`](#️-workflows) section in readme
- Add Vue support
- Add React support
- Add Tailwind support via <https://github.com/schoero/eslint-plugin-better-tailwindcss>
- Improve test setup

Any help is always greatly appreciated 🙂

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
