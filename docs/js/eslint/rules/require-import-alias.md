# `brnshkr/require-import-alias` [🔍](../../../../src/js/eslint/configs/builtin/require-import-alias.ts 'Go to source')

Relative imports that resolve into a TypeScript `paths` alias must use the alias form instead. Shorter import paths, stable across file moves, consistent across the codebase. Aliases are read from the nearest `tsconfig.json` by default; the autofix rewrites the specifier to the matching alias.

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


## Options

- `aliases` — explicit alias map in the same shape as `tsconfig#compilerOptions.paths`. Bypasses `tsconfig.json` discovery entirely; useful when aliases live outside TypeScript or need to differ from the project's TS config
- `tsConfigPath` — path to the `tsconfig.json` to load when `aliases` is not given. Defaults to the auto-resolved project config
- `ignoredPaths` — glob patterns matched against the linted file's absolute and cwd-relative paths. Files matching any pattern are skipped — useful for generated code or fixtures
