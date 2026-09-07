# `InternalExposureRule` [🔍](../../../../src/php/PhpStan/Rule/InternalExposureRule.php 'Go to source')

An `@api` symbol must name only `@api` types. Naming an `@internal` one hands a consumer a type
the rules forbid them to use, and freezes it by accident: changing it now breaks whoever read
the signature.

```php
/**
 * @internal Acme
 */
final class PasswordHasher {}

/**
 * @api
 */
final class UserService
{
    // ❌ Bad — the caller's variable is typed with a class they may not name
    public function hasher(): PasswordHasher
    {
        return new PasswordHasher();
    }

    // ❌ Bad — the same leak through a docblock type
    /**
     * @var list<PasswordHasher>
     */
    public array $hashers = [];

    // ✅ Good
    public function rehash(PasswordHasherInterface $hasher): self
    {
        return $this;
    }
}
```

## What counts as naming a type

Anything a consumer has to write, or catch, to use the symbol:

| Position | Why it counts |
| --- | --- |
| Parameter and return types | They construct the value or hold the result |
| Public property and constant types | They read or write it |
| `@throws` | They have to name the exception to `catch` it |
| A `throw` an `@api` method does not document | The same leak, and invisible to them |
| Members inherited from an `@internal` class | The signature reaches them through the public leaf |
| `@method`, `@property`, `@property-read`, `@mixin` | The tag promises a member they will call |

`@see`, `@link` and `@uses` point at code rather than promise it, so they may name anything.

## Extending an internal class is allowed

```php
/**
 * @internal Acme
 */
abstract class UserStore
{
    public function name(): string { /* … */ }
}

// ✅ Good — a consumer calls name() without ever naming UserStore
final class UserService extends UserStore {}
```

Forbidding this would drag every base class into the public API. What is checked is the surface
reaching the consumer _through_ the leaf, so a `UserStore::hasher(): PasswordHasher`
would be reported on the leaf.

## Exemptions

Private and protected members are skipped, and so is every member of a class that is not `@api`,
unless the member carries `@api` itself — the nearer tag wins. A member tagged `@internal`
is the escape hatch for a constructor a container invokes rather than a consumer.

Deleting a `@throws` does not hide an exception. It trades a visible leak for an invisible one,
and the `throw` is reported instead.
