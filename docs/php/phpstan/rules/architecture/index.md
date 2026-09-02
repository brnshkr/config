# Architecture Presets

The architecture presets are built on top of [PHPat](https://github.com/carlosas/phpat) and ship as factory methods
on the `Architecture` class. Each preset returns a list of rule services that can be passed to `setArchitecture()`.
The same method also accepts standalone rule services produced by `PhpStan::configurePhpAtTest()`,
so projects can register their own PHPat rules — with or without a preset — and mix the two freely.

> ❗ **Note** ❗  
> The same `configure*()` pattern applies outside of architecture testing: `PhpStan::configureRule()`
> produces a service definition for `setRules()`, and `PhpStan::configureStaticThrowTypeExtension()`
> produces one for `setServices()`.

## Presets

- [`Architecture::layered()`](./Layered.md) — classic three-layer Domain / Application / Infrastructure isolation
- [`Architecture::ddd()`](./Ddd.md) — full Domain-Driven Design preset built on top of `layered()`
- [`Architecture::modular()`](./Modular.md) — lightweight sibling-module isolation with no opinion on role folders
- [`Architecture::symfony()` / `laravel()` / `tempest()` / `doctrine()`](./Framework.md) — framework role-folder placement
  and isolation conventions

## Composing `setArchitecture()`

`setArchitecture()` accepts presets, standalone rules, and combinations thereof in several shapes:

```php
// Direct preset call
->setArchitecture(Architecture::ddd(...))

// Spread into a flat list
->setArchitecture([...Architecture::ddd(...)])

// Wrapped preset
->setArchitecture([Architecture::ddd(...)])

// Multiple presets
->setArchitecture([Architecture::layered(...), Architecture::modular(...)])

// Project-specific rules only
->setArchitecture([
    PhpStan::configurePhpAtTest(EmailSenderRequiresQueueRule::class, ['root' => 'Acme']),
])

// Preset + custom rules combined
->setArchitecture([
    Architecture::symfony('Acme', ['User', 'Email']),
    PhpStan::configurePhpAtTest(EmailSenderRequiresQueueRule::class, ['root' => 'Acme']),
])
```

`removeArchitecture()` accepts the same shapes, plus bare class-string entries for broad-stroke removal
and full service definitions for precise (class + arguments) removal.

> ❗ **Note** ❗  
> The rule classes live under `Brnshkr\Config\PhpStan\Rule\Architecture\<Preset>\*Test`.
> The `*Test` suffix is the PHPat naming convention for rule definitions and does _not_ indicate PHPUnit tests.

## Disabling Individual Rules

Individual rules can be disabled through `removeArchitecture()`. Passing a class name drops every instance of that rule
across all modules, while passing a full service definition removes only the instance whose `class` _and_ `arguments` match
— useful for modular layouts where the same rule class is registered once per module.

```php
use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\ControllerTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\RoleFoldersExhaustiveTest;

return PhpStan::getConfig(null, true)
    ->setArchitecture(Architecture::symfony('Acme', ['User', 'Email']))
    ->removeArchitecture([
        ControllerTest::class,
        RoleFoldersExhaustiveTest::class,
    ])
    ->toArray()
;
```
