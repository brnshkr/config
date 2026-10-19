# `FencedCodeRule` [🔍](../../../../src/php/PhpStan/Rule/FencedCodeRule.php 'Go to source')

PHP code blocks in docblocks and Markdown pages are checked like code:
the classes, methods and constants they use must exist, and every call must pass arguments its target accepts.
Names resolve through the `use` lines and the namespace of the block, then of the file.
Placeholder classes are skipped, `Acme\…` by default.
A finding carries the identifier PHPStan gives the same mistake in code, e.g. `method.notFound`.

<!-- brnshkr-skip -->
```php
use Acme\PhpStan\UserRule;
use Brnshkr\Config\PhpStan;

// ❌ Bad — the method is called `addRules()`
PhpStan::getBuilder()->addRule(UserRule::class);

// ✅ Good
PhpStan::getBuilder()->addRules([UserRule::class]);
```

A code block preceded by `<!-- brnshkr-skip -->` is left alone, for an example that is wrong on purpose:

````md
<!-- brnshkr-skip -->
```php
new Registration('user@example.com');
```
````

Code blocks are read with `league/commonmark`; without it, none are checked.

## Options

| Option | Description |
| --- | --- |
| `placeholderNamespaces` | namespaces of the placeholder classes examples may use, `['Acme']` by default |
