# 🔤 `Brnshkr/proper_name` [🔍](../../../../src/php/PhpCsFixer/Fixer/ProperNameFixer.php 'Go to source')

Names in comments are spelled the way their own projects spell them,
from the list the Markdown and script checks use.

<!-- brnshkr-skip -->
```php
// ❌ Bad
// Runs Phpstan on Github.

// ✅ Good
// Runs PHPStan on GitHub through vendor/bin/phpstan.
```

An all-lowercase name, a name joined to a path, a namespace or `::`,
and the type part of a docblock tag stay as they are. So do code, URLs and inline tags.
Twig comments get the same check from the [twig-cs-fixer config](../../TwigCsFixer.md#proper-names).
