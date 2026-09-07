# Invariants, libraries and packages

## `Architecture::baseline()`

What every preset includes. Nothing here needs configuring: the namespaces come from the project's own `composer.json`.

| Rule | Holds that |
| --- | --- |
| `NoTestDependencyTest` | production code does not depend on a namespace only `autoload-dev` loads |
| `ExceptionPlacementTest` | every exception sits in an `Exception` namespace, at any depth |
| `NoDevelopmentDependencyTest` | production code does not depend on a package production installs without |

A development-only package is every package Composer marks dev-only — `installed.json`'s `dev-package-names`,
which is transitive — plus the direct `require-dev` entries, minus anything also in `suggest`. A package in both is an optional
dependency, guarded at run time, and is left alone, which is why `symfony/finder` and `twig/twig` are not flagged.

A namespace the runtime host provides is exempt, read from production `require`:

| Requirement | Provides |
| --- | --- |
| `composer-plugin-api` | `Composer\*` — Composer loads the plugin in its own process |
| `composer-runtime-api` | `Composer\InstalledVersions`, and nothing further |

Reading the requirement rather than `type: composer-plugin` covers a package that ships Composer scripts without
being a plugin, and Composer enforces the requirement for plugins anyway. Anything else is named through `except`.

```php
Architecture::baseline(except: ['Vendor\ProvidedAtRuntime']);
```

Classmap-only packages contribute their top-level namespace through `vendor/composer/autoload_classmap.php`
— a classmapped class in the global namespace contributes nothing, since it has no namespace to forbid. A package autoloading
only through `autoload.files` is not covered at all: its functions are global, and a namespace rule cannot reach them.

## `Architecture::library()`

The baseline plus what a published package owes its consumers.

| Parameter | Adds |
| --- | --- |
| `exceptionInterface` | every exception implements it, so one `catch` covers the package |
| `model` + `isolatedFrom` | the model does not depend on the libraries that populate it |
| `facades` | the classes behind a facade do not reach back through it |

```php
Architecture::library(
    exceptionInterface: 'Acme\Exception\ExceptionInterface',
    model: 'Acme\Model',
    isolatedFrom: ['PhpParser', 'Symfony'],
    facades: ['Filter' => 'Acme\Filter'],
);
```

Each parameter is optional; omitting one drops its rule.

## Package variants of the framework presets

`symfonyBundle()`, `laravelPackage()` and `tempestPackage()` are the framework presets for code that ships
to somebody else's application. They carry the baseline, the framework's placement rules,
and one more: a package may not depend on the namespace of the application installing it.

`symfonyBundle()` also drops the fixture placement rule, which is an application concern.

```php
Architecture::symfonyBundle();
Architecture::laravelPackage(application: 'App');
```

Two things a Symfony bundle needs are handled outside these presets. A project whose `composer.json`
declares `type: symfony-bundle` automatically ignores `symfony.preferAutowireAttributeOverConfigParam`,
because a reusable bundle defines its services rather than autowiring them. That rules-out-autowiring expectation
itself is not enforced: PHPat can assert that a class _applies_ an attribute, but has no negative form.
