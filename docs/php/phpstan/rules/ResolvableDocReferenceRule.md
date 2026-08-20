# `ResolvableDocReferenceRule` [🔍](../../../../src/php/PhpStan/Rule/ResolvableDocReferenceRule.php 'Go to source')

A `@see` target must name a symbol that exists, so a reference that no longer points anywhere is reported instead of silently rotting. Targets resolve through the file's namespace and its `use` statements.

```php
namespace Acme\User;

use Acme\Email\Mailer;

/**
 * ❌ Bad — neither in this namespace nor imported
 * {@see PasswordHasher}
 *
 * ❌ Bad — the class resolves, the method does not
 * {@see Mailer::sendNow()}
 *
 * ✅ Good — imported
 * {@see Mailer}
 *
 * ✅ Good — fully qualified
 * {@see \Acme\User\Internal\PasswordHasher}
 */
final class Registration {}
```

## Members

A member is named after a double colon, and how it is written picks the kind: `Acme\User::hash()` a method, `Acme\User::$hash` a property, `Acme\User::HASH` a constant. Without a class, a target ending in `()` names a global function; anything else names a class, interface, trait, enum or global constant.

`self` and `parent` must match where the member is declared — the surrounding class and its parent. `static` is allowed only for an abstract member, where it means the subclass's implementation.

## URIs

A URI must be complete. `@link` takes one and nothing else, `@see` takes one alongside a symbol, and either is reported without a scheme — `doc://getting-started/index` passes, `docs/index.md` does not.

## Free text

A target ends at the first space, so the rest is a description. A block `@see` may carry free text, which is why a lowercase target under it is left alone; an inline `{@see …}` names a symbol and is checked whatever its case.
