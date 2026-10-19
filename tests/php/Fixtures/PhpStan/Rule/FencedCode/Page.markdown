# Page

```php
use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Builder;

Builder::create()->build();
Builder::missing(); // ERROR Method `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Builder::missing()` does not exist.
```

```php
/**
 * ```php
 * Missing::create(); // ERROR Class `Missing` does not exist.
 * ```
 */
function acme(): void {}
```
