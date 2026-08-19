# PHP-CS-Fixer [🔍](../../src/php/PhpCsFixer.php 'Go to source')

`Brnshkr\Config\PhpCsFixer::getConfig()` builds a ready-to-use PHP-CS-Fixer config that captures the @brnshkr coding-style decisions. It extends the `@PhpCsFixer` and `@PhpCsFixer:risky` presets with project-specific opinions around alignment, ordering, native-function invocation, and PHPDoc layout. When the optional [`kubawerlos/php-cs-fixer-custom-fixers`](https://github.com/kubawerlos/php-cs-fixer-custom-fixers) package is installed, its fixers are layered on top automatically.

## Usage

```php
use Brnshkr\Config\PhpCsFixer;

return PhpCsFixer::getConfig();
```

Scope the run with a [`FileFinder`](./FileFinder.md) argument. `getConfig()` returns a `PhpCsFixer\Config`; customizing it further is PHP-CS-Fixer's own API.
