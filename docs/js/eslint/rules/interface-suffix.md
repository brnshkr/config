# `brnshkr/interface-suffix` [🔍](../../../../src/js/eslint/configs/builtin/interface-suffix.ts 'Go to source')

JavaScript mirror of the PHP [`InterfaceSuffixRule`](../../../php/phpstan/rules/InterfaceSuffixRule.md), which is the source of truth for the convention. A class implementing a single `*Interface` must end with the matching prefix, so the contract and the implementation stay paired at the call site.

```ts
interface UserRepositoryInterface {}

// ❌ Bad — implements `UserRepositoryInterface` but does not end with `UserRepository`
class InMemoryUsers implements UserRepositoryInterface {}

// ✅ Good
class InMemoryUserRepository implements UserRepositoryInterface {}
```

## Multi-interface skip

When a class implements more than one `*Interface`, no single prefix is canonical, so the rule steps back and leaves naming to the author:

```ts
interface UserRepositoryInterface {}
interface CacheableInterface {}

// ✅ Skipped — two `*Interface` implementations make the suffix ambiguous
class CachedUserStore implements UserRepositoryInterface, CacheableInterface {}
```

Parents that do not end with `Interface` — a base class, a marker type, an unsuffixed interface — are ignored for the count. They neither trigger nor suppress the check.

## Differences from the PHP rule

- TypeScript is structurally typed, so an `implements` clause is optional. The rule sees only explicit clauses and cannot judge a class that satisfies a contract without naming it.
- an implemented name may be qualified (`Acme.UserRepositoryInterface`); the comparison uses its last segment
- TypeScript allows `implements` on a type alias as well as on an interface. Both are treated alike, since the name is what carries the convention.
- a class expression without a name is skipped, mirroring how the PHP rule skips anonymous classes
