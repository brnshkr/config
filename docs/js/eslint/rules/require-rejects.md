# `brnshkr/require-rejects` [🔍](../../../../src/js/eslint/configs/builtin/require-rejects.ts 'Go to source')

The promise counterpart of [`brnshkr/require-throws`](./require-throws.md).
A function declares every error its promise can reject with under `@rejects`: a throw inside an async function,
a `reject()` or `Promise.reject()` it returns, and a callee's `@rejects` once it is awaited or returned.

```ts
import { readFile } from 'node:fs/promises';

// ❌ Bad — `JSON.parse()` throws a `SyntaxError`, which rejects the promise here
export const readManifest = async (): Promise<unknown> => JSON.parse(await readFile('./package.json', 'utf-8'));

// ✅ Good
/**
 * @rejects {SyntaxError} when the manifest holds no JSON
 */
export const readManifest = async (): Promise<unknown> => JSON.parse(await readFile('./package.json', 'utf-8'));
```

## Exemptions

A promise that is neither awaited nor returned rejects somewhere else, and a `try` with a `catch` stops what it awaits.
Test files are exempt, as `autoload-dev` is in PHP.
Without type information, only a function's own throws are checked, and any declared type covers them.

## Options

- `uncheckedErrors` — error classes that need no tag, their subclasses included, e.g. `['TypeError']`
