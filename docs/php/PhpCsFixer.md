# PHP-CS-Fixer [🔍](../../src/php/PhpCsFixer.php 'Go to source')

`Brnshkr\Config\PhpCsFixer` is the @brnshkr coding-style configuration, ready to return from a config file.

## Usage

```php
// ./conf/php-cs-fixer.dist.php
use Brnshkr\Config\PhpCsFixer;

return PhpCsFixer::getConfig();
```

Pass a [Finder](https://symfony.com/doc/current/components/finder.html) to narrow the scope
— [`FileFinder`](./FileFinder.md) applies the shared exclusions either way.

## Customizing

`getBuilder()` gives the same configuration as a builder, and `build()` finishes it.

```php
return PhpCsFixer::getBuilder()
    ->addRules(['numeric_literal_separator' => true])
    ->build()
;
```

Rules take `addRules()`, `setRules()` and `removeRules()`. Reach for those rather than the returned config's
own `setRules()`, which replaces the baseline instead of merging into it.

A private `./conf/php-cs-fixer.php` starts from the tracked config rather than from the defaults, so what
the repository configured survives. `from()` takes what that file returns:

```php
// ./conf/php-cs-fixer.php
$config = include __DIR__ . '/php-cs-fixer.dist.php';

return PhpCsFixer::from($config)
    ->addRules(['numeric_literal_separator' => true])
    ->build()
;
```

A repository that tracks no config of its own has nothing to include; `getBuilder()` above
starts from the same defaults that file would have carried.
