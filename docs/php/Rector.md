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
