# `FencedCodeRule` [🔍](../../../../src/php/PhpStan/Rule/FencedCodeRule.php 'Go to source')

Code blocks in docblocks and Markdown pages are checked like code, so an example cannot drift from what it documents.
A code block preceded by `<!-- brnshkr-skip -->` is left alone, for an example that is wrong on purpose:

````md
<!-- brnshkr-skip -->
```php
new Registration('user@example.com');
```
````

Code blocks are read with `league/commonmark`; without it, none are checked.

## PHP

The classes, methods and constants a block uses must exist, and every call must pass arguments its target accepts.
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

## XML

A block must parse, and must match the schema its root names when that path exists in the project.
Schemas are never downloaded. Without `ext-dom`, XML blocks are skipped.

<!-- brnshkr-skip -->
```xml
<!-- ❌ Bad — PHPUnit knows no <tests> element -->
<phpunit
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
>
  <tests/>
</phpunit>
```

## Makefile

A block must parse with `make`. An `include` of the shared Makefile reads the installed copy, any other is made optional.
One including the shared Makefile must also pass `make help`.
Without `make`, Makefile blocks are skipped.

<!-- brnshkr-skip -->
```Makefile
# ❌ Bad — the recipe is indented with spaces, not a tab
acme-greet:
    @echo hello
```

XML and Makefile findings carry the identifier `brnshkr.fencedCode`.

## Options

| Option | Description |
| --- | --- |
| `placeholderNamespaces` | namespaces of the placeholder classes examples may use, `['Acme']` by default |
