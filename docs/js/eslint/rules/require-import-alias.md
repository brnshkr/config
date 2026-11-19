# `brnshkr/require-import-alias` [🔍](../../../../src/js/eslint/configs/builtin/require-import-alias.ts 'Go to source')

A relative import that resolves into an alias must use the alias, which stays short and survives file moves;
the autofix rewrites it. Aliases come from `compilerOptions.paths` in the `tsconfig.json` and from the `imports`
of the `package.json` nearest the linted file. A conditional `imports` entry resolves the way TypeScript's bundler
mode does, through `types`, `import`, `default` and the tsconfig's `customConditions`.

```jsonc
// ./tsconfig.json
{
  "compilerOptions": {
    "paths": {
      "$user/*": ["./src/user/*"],
      "$email/*": ["./src/email/*"]
    }
  }
}
```

```js
// ❌ Bad — relative path resolves into the '$user/*' alias
import { UserService } from '../../user/UserService';

// ✅ Good
import { UserService } from '$user/UserService';
```

The check applies to `import`, `export ... from`, `export * from`, and dynamic `import()` specifiers alike:

```js
// ❌ Bad — re-export through a relative path that resolves into an alias root
export { EmailNotifier } from '../../email/EmailNotifier';

// ✅ Good
export { EmailNotifier } from '$email/EmailNotifier';
```

```js
// ❌ Bad — dynamic import of an aliased target via relative path
const emailNotifierModule = await import('../../email/EmailNotifier');

// ✅ Good
const emailNotifierModule = await import('$email/EmailNotifier');
```

## Overlapping aliases

When more than one alias can express the same target, the one with the **fewest path segments** wins
— not the shortest string. A nested alias exists to shorten the paths below it, so reaching its files
through an outer alias is reported too, even though the specifier is already aliased.

Given `$src/*` pointing at `./src/*` next to the `$user/*` alias above:

```js
// ❌ Bad — three segments, and '$user/*' covers the target
import { UserService } from '$src/user/UserService';

// ✅ Good — two segments
import { UserService } from '$user/UserService';
```

## Options

- `aliases` — an explicit map in the shape of `compilerOptions.paths`, used instead of both sources
- `tsConfigPath` — the `tsconfig.json` read for `paths` and `customConditions`; defaults to the project's own
- `ignoredPaths` — glob patterns matched against the linted file's absolute and cwd-relative paths. Files matching any
  pattern are skipped — useful for generated code or fixtures
