# `FencedCodeRector` [🔍](../../../../src/php/Rector/Rule/FencedCodeRector.php 'Go to source')

PHP code blocks in docblocks and Markdown pages are refactored with the configured rules,
except rules that only fit a whole file or would change what an example shows.
The report names the rules that changed a block, e.g. `SimplifyIfReturnBoolRector`.

<!-- brnshkr-skip -->
```php
// ❌ Bad
if ($isActive === true) {
    return true;
}

return false;

// ✅ Good
return $isActive === true;
```

A code block preceded by `<!-- brnshkr-skip -->` is left alone, and so is one Rector cannot analyze.
Code blocks are read with `league/commonmark`; without it, none are refactored.

## Options

| Option | Description |
| --- | --- |
| `FencedCodeRector::SKIPS` | further rules kept out of code blocks, added to by every config that names it |
