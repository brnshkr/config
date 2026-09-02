# `Architecture::ddd()` [🔍](../../../../../src/php/PhpStan/Rule/Architecture/Architecture.php 'Go to source')

A full Domain-Driven Design preset. Composes `layered()` and adds per-module `Domain` and `Application` isolation,
an optional `Interface` layer (entry points such as HTTP controllers and CLI commands),
`final readonly` contracts for value objects and domain events, and a configurable list of
framework namespaces that the `Domain` layer must not depend on.
Module isolation rules are emitted only when at least two modules are configured.

```php
use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;

return PhpStan::getConfig(null, true)
    ->setArchitecture(Architecture::ddd(
        modules: ['User', 'Email'],
        interface: 'Acme\Interface',
        valueObject: 'Acme\Domain\ValueObject',
        domainEvent: 'Acme\Domain\Event',
        isolatedFrom: ['Doctrine\ORM', 'Symfony\Component\HttpFoundation'],
    ))
    ->toArray()
;
```

## Caught violations

Cross-module Domain reach:

```php
namespace Acme\Domain\Email;

use Acme\Domain\User\User;

// ❌ Bad — 'Email' Domain depends on 'User' Domain
final class WelcomeEmail
{
    public function isAddressedTo(User $user): bool {}
}
```

Framework leak into Domain:

```php
namespace Acme\Domain\User;

use Doctrine\ORM\Mapping as ORM;

// ❌ Bad — Domain class importing from 'Doctrine\ORM' (listed in 'isolatedFrom')
#[ORM\Entity]
final class User {}
```

Mutable value object:

```php
namespace Acme\Domain\ValueObject;

// ❌ Bad — value object must be 'final readonly'
final class EmailAddress
{
    public string $localPart;
    public string $domain;
}

// ✅ Good
final readonly class EmailAddress
{
    public function __construct(
        public string $localPart,
        public string $domain,
    ) {}
}
```
