# Twig-CS-Fixer [🔍](../../src/php/TwigCsFixer.php 'Go to source')

`Brnshkr\Config\TwigCsFixer::getConfig()` builds a ready-to-use Twig-CS-Fixer config that captures the @brnshkr template-style decisions — file naming, include handling, macro and named-argument conventions, hash compaction, and constant-function validation among them.

## Usage

```php
use Brnshkr\Config\TwigCsFixer;

return TwigCsFixer::getConfig();
```

Scope the run with a [`FileFinder`](./FileFinder.md) argument, restricted to the Twig extension. `getConfig()` returns a `TwigCsFixer\Config\Config`; customizing it further is Twig-CS-Fixer's own API.
