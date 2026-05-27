# Twig-CS-Fixer [🔍](../../src/php/TwigCsFixer.php 'Go to source')

`Brnshkr\Config\TwigCsFixer::getConfig()` builds a ready-to-use Twig-CS-Fixer config that captures the @brnshkr template-style decisions — file naming, include handling, macro and named-argument conventions, hash compaction, and constant-function validation among them.

## Usage

```php
use Brnshkr\Config\TwigCsFixer;

return TwigCsFixer::getConfig();
```

## Scoping the run

Pass a pre-configured [Symfony Finder](https://symfony.com/doc/current/components/finder.html) as the first argument to narrow which templates are processed; otherwise the shared [`FileFinder`](./FileFinder.md) defaults apply, restricted to the Twig extension.

```php
use Brnshkr\Config\TwigCsFixer;
use Symfony\Component\Finder\Finder;

return TwigCsFixer::getConfig(new Finder()->in('templates'));
```

## Customizing the returned config

`getConfig()` returns a `TwigCsFixer\Config\Config` instance. This package only layers the @brnshkr defaults on top — any further customization is Twig-CS-Fixer's own API, documented upstream.
