# `Brnshkr/fenced_code` [🔍](../../../../src/php/PhpCsFixer/Fixer/FencedCodeFixer.php 'Go to source')

PHP code blocks in docblocks and Markdown pages are formatted with the configured rules,
except those that only fit a whole file.

<!-- brnshkr-skip -->
```php
// ❌ Bad
$user=new User( 'acme' );

// ✅ Good
$user = new User('acme');
```

Twig code blocks are formatted with the project's twig-cs-fixer rules while twig-cs-fixer is installed.
A code block preceded by `<!-- brnshkr-skip -->` is left alone, and so is one that does not parse.
Code blocks are read with `league/commonmark`; without it, none are formatted.

## Options

| Option | Description |
| --- | --- |
| `excluded_rules` | further rules kept out of code blocks |
