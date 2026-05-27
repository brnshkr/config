# Rector [🔍](../../src/php/Rector.php 'Go to source')

`Brnshkr\Config\Rector::getConfig()` builds a ready-to-use Rector config that captures the @brnshkr refactoring decisions. Among them, the `#[\SensitiveParameter]` attribute rule comes pre-wired to a list of parameter names commonly associated with secrets (`password`, `apiToken`, `clientSecret`, and many others), with plural variants generated at runtime, so newly introduced sensitive parameters automatically get the attribute added.

## Usage

```php
use Brnshkr\Config\Rector;

return Rector::getConfig();
```

## Scoping the run

Pass a pre-configured [Symfony Finder](https://symfony.com/doc/current/components/finder.html) as the first argument to narrow which files Rector processes; otherwise the shared [`FileFinder`](./FileFinder.md) defaults apply.

```php
use Brnshkr\Config\Rector;
use Symfony\Component\Finder\Finder;

return Rector::getConfig(new Finder()->in('src'));
```

## Customizing the returned config

`getConfig()` returns a `RectorConfigBuilder`. This package only layers the @brnshkr defaults on top — any further customization is Rector's own API, documented upstream.
