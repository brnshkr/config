# Twig-CS-Fixer [🔍](../../src/php/TwigCsFixer.php 'Go to source')

`Brnshkr\Config\TwigCsFixer` is the @brnshkr template-style configuration, ready to return from a config file.

## Usage

```php
// ./conf/twig-cs-fixer.dist.php
use Brnshkr\Config\TwigCsFixer;

return TwigCsFixer::getConfig();
```

Pass a [Finder](https://symfony.com/doc/current/components/finder.html) to narrow the scope
— [`FileFinder`](./FileFinder.md) applies the shared exclusions either way.

## Customizing

`getBuilder()` gives the same configuration as a builder, and `build()` finishes it.

```php
return TwigCsFixer::getBuilder()
    ->addRules([new FileExtensionRule()])
    ->build()
;
```

Rules take `addRules()`, `setRules()` and `removeRules()`, plus `overrideRules()` to swap one for another of the same
class. Reach for those rather than the ruleset's own `setRuleset()`, which replaces the whole set.
