# `Architecture::symfony()` / `laravel()` / `tempest()` / `doctrine()` [🔍](../../../../../src/php/PhpStan/Rule/Architecture/Architecture.php 'Go to source')

The framework presets enforce the role-folder placement and isolation conventions typical for each framework:
Symfony Controllers must live in `Acme\Controller\*`, Doctrine `EntityManagerInterface` is forbidden from Controllers,
Doctrine repositories must extend the appropriate base class, Tempest routes must not touch the database directly,
and so on. Each preset shares the same factory shape, so they compose freely with each other
and with the [`modular()`](./Modular.md) / [`ddd()`](./Ddd.md) presets.

The same factory supports two project layouts:

- **Flat layout** — when only the root namespace is provided, the rules apply to a single, flat root
- **Package-by-feature layout** — when a `modules` list is also passed, the rules are scoped per module-root
  and every preset additionally emits a `RoleFoldersExhaustiveTest` rule
  that forbids stray classes outside the canonical role folders

```php
use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;

return PhpStan::getConfig(null, true)
    ->setArchitecture(Architecture::symfony(root: 'Acme'))
    ->toArray()
;
```

```text
src/
    Controller/
    Security/
    Service/
    Entity/
    Repository/
    ...
```

```php
use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;

return PhpStan::getConfig(null, true)
    ->setArchitecture([
        Architecture::symfony(root: 'Acme', modules: ['User', 'Email']),
        Architecture::doctrine(root: 'Acme', migrationsNamespace: 'Acme\Migrations', modules: ['User', 'Email']),
    ])
    ->toArray()
;
```

```text
src/
    User/
        Controller/
        Service/
        Entity/
        Repository/
    Email/
        Controller/
        Service/
        Entity/
        Repository/
```

## Caught violation

Controller placed outside the canonical role folder:

```php
namespace Acme\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// ❌ Bad — Controllers must live under 'Acme\Controller\*', not 'Acme\Http\*'
final class UserController extends AbstractController {}
```

```php
namespace Acme\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// ✅ Good
final class UserController extends AbstractController {}
```

In a package-by-feature layout the same check fires per module — a `UserController` under `Acme\User\Http` is reported,
while `Acme\User\Controller\UserController` is accepted.

## Framework-specific extras

Each preset adds checks particular to its framework. A few highlights:

- **`symfony()`** — controllers, voters, form types, event listeners, message handlers, authenticators,
  normalizers, and friends must each live in their canonical role folder;
  entities may not import from `Symfony\Component\HttpFoundation`
- **`laravel()`** — Eloquent models, HTTP controllers, form requests,
  jobs, and mailables follow the same role-folder discipline
- **`tempest()`** — routes must not touch the database directly, and the console layer may not depend on the HTTP layer
- **`doctrine()`** — repositories must extend the configured base repository,
  `EntityManagerInterface` is forbidden from controllers,
  and migrations live under the configured migrations namespace

For code that ships to somebody else's application rather than being one, see the [package variants](./Library.md).
