# `InterfaceSuffixRule` [🔍](../../../../src/php/PhpStan/Rule/InterfaceSuffixRule.php 'Go to source')

Classes that implement a single `<Prefix>Interface` must end with the matching `<Prefix>`. Pairs the contract and the implementation visibly at the call site and keeps grep-able naming consistent across the codebase. Only triggered when exactly one `*Interface`-suffixed interface is implemented — zero or many such interfaces make the canonical suffix ambiguous and are skipped.

```php
interface UserRepositoryInterface {}

// ❌ Bad — implements 'UserRepositoryInterface' but does not end with 'UserRepository'
final class DoctrineUsers implements UserRepositoryInterface {}

// ✅ Good
final class DoctrineUserRepository implements UserRepositoryInterface {}
```

## Multi-interface skip

When a class implements more than one `*Interface`-suffixed interface, no single prefix is canonical, so the rule steps back and leaves naming to the author:

```php
interface UserRepositoryInterface {}
interface CacheableInterface {}

// ✅ Skipped — two '*Interface' implementations make the suffix ambiguous
final class CachedUserStore implements UserRepositoryInterface, CacheableInterface {}
```

Non-`*Interface` parents (a base class, a non-suffixed interface, a marker like `\Stringable`) are ignored for the count — they neither trigger nor suppress the check.
