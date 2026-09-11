# FileFinder [🔍](../../src/php/FileFinder.php 'Go to source')

`Brnshkr\Config\FileFinder` is the file discovery every PHP tool configuration in this package runs on.

## Usage

```php
use Brnshkr\Config\FileFinder;

$phpFiles = FileFinder::get();
```

It collects the PHP files below the working directory, `./bin/console` included, and leaves out what no tool
should read — e.g. dependencies, caches, build output and fixtures.
`getFilePaths()` returns the same selection as plain paths instead of a `Finder`.

## Customizing

Both take a [Finder](https://symfony.com/doc/current/components/finder.html) to narrow the scope,
and the extensions to collect.

```php
$twigFiles = FileFinder::get(new Finder()->in('templates'), FileFinder::EXTENSION_TWIG);
```

`FileFinder::EXTENSIONS` holds what is supported; anything else throws `InvalidArgumentException`.
