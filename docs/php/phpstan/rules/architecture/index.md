# Architecture presets

The architecture presets are built on [PHPat](https://github.com/carlosas/phpat) and ship as factory methods
on the `Architecture` class. Each returns a list of rule services for `addArchitecture()`, which also accepts
standalone rules from `PhpStan::configurePhpAtTest()`, so a project can mix presets and its own rules freely.

Namespaces are derived from the project's own `composer.json`, so a preset called
with no arguments is correct in any repository. Passing one explicitly overrides the derived value.

## Presets

| Preset | Covers |
| --- | --- |
| [`baseline()`](./Library.md) | what every preset includes: tests, exceptions and development-only packages |
| [`library()`](./Library.md) | a published package — its exception interface, its model, its facades |
| [`layered()`](./Layered.md) | Domain / Application / Infrastructure isolation |
| [`ddd()`](./Ddd.md) | full Domain-Driven Design, built on `layered()` |
| [`modular()`](./Modular.md) | sibling-module isolation, no opinion on role folders |
| [`symfony()` / `laravel()` / `tempest()` / `doctrine()`](./Framework.md) | framework role-folder placement and isolation |
| [`symfonyBundle()` / `laravelPackage()` / `tempestPackage()`](./Library.md) | the same, for a package rather than an application |

Every preset carries the [baseline](./Library.md), so composing a second preset to get them is never necessary.

## Composing

```php
return PhpStan::getBuilder()
    ->addArchitecture(Architecture::ddd())
    ->addArchitecture([
        Architecture::layered(), 
        Architecture::modular(modules: ['User', 'Email']),
    ])
    ->addArchitecture([
        Architecture::symfony(root: 'Acme'),
        PhpStan::configurePhpAtTest(EmailSenderRequiresQueueRule::class, [
          'roots' => ['Acme'],
        ]),
    ])
    ->build()
;
```

Presets, single rules and lists of either are all accepted, at any nesting.

Registering the same rule class twice with different arguments is an error rather than a silent loss:
PHPat keeps one instance per test class and would drop the second. A rule that applies to several namespaces
therefore takes a list — `['roots' => ['Acme\User', 'Acme\Email']]` — and yields one rule per entry.

> ❗ **Note** ❗  
> The rule classes live under `Brnshkr\Config\PhpStan\Rule\Architecture\<Preset>\*Test`. The `*Test` suffix is PHPat's
> naming convention and does _not_ indicate PHPUnit tests.

## Removing rules

`removeArchitecture()` takes a class-string to drop a rule entirely,
or a full service definition to drop only the instance whose class _and_ arguments match.

```php
return PhpStan::getBuilder()
    ->addArchitecture(Architecture::symfony(root: 'Acme'))
    ->removeArchitecture([ControllerTest::class])
    ->build()
;
```
