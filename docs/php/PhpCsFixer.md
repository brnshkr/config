# PHP-CS-Fixer [🔍](../../src/php/PhpCsFixer.php 'Go to source')

`Brnshkr\Config\PhpCsFixer::getConfig()` builds a ready-to-use PHP-CS-Fixer config that captures the @brnshkr coding-style decisions. It extends the `@PhpCsFixer` and `@PhpCsFixer:risky` presets with project-specific opinions around alignment, ordering, native-function invocation, and PHPDoc layout. When the optional [`kubawerlos/php-cs-fixer-custom-fixers`](https://github.com/kubawerlos/php-cs-fixer-custom-fixers) package is installed, its fixers are layered on top automatically.

## Usage

```php
use Brnshkr\Config\PhpCsFixer;

return PhpCsFixer::getConfig();
```

## Scoping the run

Pass a pre-configured [Symfony Finder](https://symfony.com/doc/current/components/finder.html) as the first argument to narrow which files are processed; otherwise the shared [`FileFinder`](./FileFinder.md) defaults apply.

```php
use Brnshkr\Config\PhpCsFixer;
use Symfony\Component\Finder\Finder;

return PhpCsFixer::getConfig(new Finder()->in('src'));
```

## Customizing the returned config

`getConfig()` returns a `PhpCsFixer\Config` instance. This package only layers the @brnshkr defaults on top — any further customization is PHP-CS-Fixer's own API, documented upstream.
