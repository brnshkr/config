# `Architecture::modular()` [🔍](../../../../../src/php/PhpStan/Rule/Architecture/Architecture.php 'Go to source')

A lightweight modular preset that enforces only sibling-module isolation, without any opinion on role folders.
Each configured module is forbidden from depending on its siblings. The `pattern` argument describes how
a module short-name maps to its full namespace through a `{name}` placeholder. At least two modules are required.

```php
use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;

return PhpStan::getConfig(null, true)
    ->setArchitecture(Architecture::modular(
        modules: ['User', 'Email'],
        pattern: 'Acme\{name}',
    ))
    ->toArray()
;
```

## `pattern` variants

The `{name}` placeholder can sit anywhere in the namespace. A nested module root works just as well:

```php
return PhpStan::getConfig(null, true)
    ->setArchitecture(Architecture::modular(
        modules: ['User', 'Email'],
        pattern: 'Acme\Modules\{name}\Domain',
    ))
    ->toArray()
;
```

## Caught violation

```php
namespace Acme\Email\Notifier;

use Acme\User\User;

// ❌ Bad — 'Email' depends on its sibling 'User'
final class WelcomeEmailNotifier
{
    public function sendTo(User $user): void {}
}
```
