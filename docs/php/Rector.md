# Rector [🔍](../../src/php/Rector.php 'Go to source')

`Brnshkr\Config\Rector` is the @brnshkr Rector configuration, ready to return from a config file.

## Usage

```php
// ./conf/rector.dist.php
use Brnshkr\Config\Rector;

return Rector::getConfig();
```

Pass a [Finder](https://symfony.com/doc/current/components/finder.html) to narrow the scope
— [`FileFinder`](./FileFinder.md) applies the shared exclusions either way.

## Customizing

`getBuilder()` gives the same configuration as a builder, and `build()` finishes it.

```php
return Rector::getBuilder()
    ->addSkips([SomeRector::class])
    ->build()
;
```

Rules, paths and skips each take `add*`, `set*` and `remove*`.
`removeRules()` skips instead of subtracting, because the baseline's rules arrive through prepared sets.
Anything not wrapped here is reachable on the `RectorConfigBuilder` that `build()` returns.

A private `./conf/rector.php` starts from the tracked config rather than from the defaults,
so what the repository configured survives. `from()` takes what that file returns:

```php
// ./conf/rector.php
$config = include __DIR__ . '/rector.dist.php';

return Rector::from($config)
    ->addSkips([SomeRector::class])
    ->build()
;
```

A repository that tracks no config of its own has nothing to include; `getBuilder()` above
starts from the same defaults that file would have carried.
