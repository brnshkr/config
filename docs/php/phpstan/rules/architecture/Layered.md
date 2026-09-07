# `Architecture::layered()` [🔍](../../../../../src/php/PhpStan/Rule/Architecture/Architecture.php 'Go to source')

A classic three-layer separation: the `Domain` layer may not depend on either `Application` or `Infrastructure`,
and the `Application` layer may not depend on `Infrastructure`. Serves both as the foundation that `ddd()`
builds on and as a standalone preset for projects that want layered isolation without the rest of the DDD machinery.

```php
use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;

return PhpStan::getConfig(null, true)
    ->setArchitecture(Architecture::layered(
        domain: 'Acme\Domain',
        application: 'Acme\Application',
        infrastructure: 'Acme\Infrastructure',
    ))
    ->toArray()
;
```

## Caught violations

```php
namespace Acme\Domain\User;

use Acme\Infrastructure\Persistence\DoctrineUserRepository;

// ❌ Bad — 'Domain' reaches into 'Infrastructure'
final class User
{
    public function __construct(
        private DoctrineUserRepository $userRepository,
    ) {}
}
```

```php
namespace Acme\Domain\User;

// ✅ Good — 'Domain' depends only on a 'Domain' contract; the implementation lives in 'Infrastructure'
final class User
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}
}
```

The same shape catches `Application → Infrastructure` leaks:
an `Acme\Application\*` class importing from `Acme\Infrastructure\*` triggers the rule.
