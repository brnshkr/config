# `brnshkr/no-php-open-tag` [🔍](../../../../src/js/markdownlint/configs/builtin/no-php-open-tag.ts 'Go to source')

The PHP configs of this package read a Markdown page as HTML, until a `<?` turns the rest of it into PHP.
PHP's default `short_open_tag` reads `<?xml` as one too.
So no `<?` may stand anywhere in a page, inline code and other languages' code blocks included.
A `php` code block also leaves out `declare(strict_types=1);`.

````md
<!-- ❌ Bad — the open tag hands the rest of the page to PHP -->
```php
<?php

declare(strict_types=1);

return PhpStan::getConfig();
```

<!-- ✅ Good — the code block holds the code alone -->
```php
return PhpStan::getConfig();
```
````
