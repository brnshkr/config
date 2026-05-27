# FileFinder [🔍](../../src/php/FileFinder.php 'Go to source')

`Brnshkr\Config\FileFinder` is the shared file-discovery helper that every PHP tool config in this package delegates to. Passing `null` as an argument for the `$finder` parameter of [`PhpCsFixer`](./PhpCsFixer.md), [`Rector`](./Rector.md), [`TwigCsFixer`](./TwigCsFixer.md), and [`PhpStan`](./phpstan/index.md) hands scope resolution to `FileFinder`.

## What it does

- Scans the current working directory (or a caller-provided [Symfony Finder](https://symfony.com/doc/current/components/finder.html), narrowed further).
- Filters by extension — PHP, Twig, or both. The supported set is exposed as `FileFinder::EXTENSIONS`, with the individual values available as `FileFinder::EXTENSION_PHP` and `FileFinder::EXTENSION_TWIG`. Passing an unsupported extension throws `InvalidArgumentException`.
- Excludes the usual noise: `vendor`, `node_modules`, `var`, `.cache`, `.local`, `config/reference.php`, and the `tests/**/Fixtures` / `tests/**/coverage` folders.
- Also picks up `bin/console` when PHP files are requested.

## Usage

```php
use Brnshkr\Config\FileFinder;

$phpFiles        = FileFinder::get();
$phpAndTwigFiles = FileFinder::get(null, [FileFinder::EXTENSION_PHP, FileFinder::EXTENSION_TWIG]);
$scopedTwigFiles = FileFinder::get(new Finder()->in('templates'), FileFinder::EXTENSION_TWIG);
```

`FileFinder::getFilePaths()` returns the same selection as a flat array of paths instead of a `Finder`.
