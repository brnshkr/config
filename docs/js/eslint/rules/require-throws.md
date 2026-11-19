# `brnshkr/require-throws` [🔍](../../../../src/js/eslint/configs/builtin/require-throws.ts 'Go to source')

JavaScript mirror of PHPStan's [checked exceptions](https://phpstan.org/blog/bring-your-exceptions-under-control).
A function declares every error it can raise, its callees' included, so a caller learns them from the docblock
rather than from a body it never reads. A declared class covers its subclasses.

```ts
/**
 * @throws {Error} when the user is unknown
 */
const readUser = (id: string): string => {
  if (id === '') {
    throw new Error('Unknown user.');
  }

  return id;
};

// ❌ Bad — `readUser` throws, and so does the callback that calls it
export const readUsers = (ids: string[]): string[] => ids.map((id) => readUser(id));

// ✅ Good
/**
 * @throws {Error} when a user is unknown
 */
export const readUsers = (ids: string[]): string[] => ids.map((id) => readUser(id));
```

## Exemptions

A `try` with a `catch` stops whatever its block throws, and an inline callback counts for the function around it.
A throw inside an async function rejects its promise instead, which [`brnshkr/require-rejects`](./require-rejects.md) checks.
A `TypeError` that a `@types` package declares reports an argument of the wrong type,
so it is asked for only in JavaScript, where an untyped or `unknown` argument can still be one.
Test files are exempt, as `autoload-dev` is in PHP.
Without type information, only a function's own throws are checked, and any declared type covers them.

## Options

- `uncheckedErrors` — error classes that need no tag, their subclasses included, e.g. `['TypeError']`
