# `brnshkr/internal-usage` [🔍](../../../../src/js/eslint/configs/builtin/internal-usage.ts 'Go to source')

JavaScript mirror of the PHP [`InternalUsageRule`](../../../php/phpstan/rules/InternalUsageRule.md), which is the source
of truth for the subtree rule, the explicit target, the bare vendor form, and what each option matches.
A symbol marked `@internal` may only be used from inside its own namespace or a sub-namespace of it.

A module's namespace is the `name` of its nearest `package.json` followed by the module's directory relative
to that package root, with leading `dist`, `js`, `lib` and `src` segments dropped so that the namespace
reads like the specifier a consumer writes. So `./src/internal/hasher.ts` in the package `@acme/user`
declares into `@acme/user/internal`, and the module itself is `@acme/user/internal/hasher`.
`package.json#exports` play no part: the modules this rule protects are the ones `exports` does not name.

```js
// ./src/internal/hasher.ts
/**
 * @internal
 */
export class PasswordHasher {}
```

```js
// ❌ Bad — src/public/registration.ts sits in a sibling namespace
import { PasswordHasher } from '../internal/hasher';

export const hasher = new PasswordHasher();
```

```js
// ✅ Good — src/internal/hashing/legacy.ts sits below the declaring namespace
import { PasswordHasher } from '../hasher';

export const hasher = new PasswordHasher();
```

## Naming a target

`@internal @acme/email` replaces the declaring namespace, `@internal @acme` opens the symbol to every package
of that scope, and `@internal acme-app` opens a whole unscoped package. Because the tag carries an argument here,
the shipped config stops applying `jsdoc/empty-tags` to `@internal` whenever the jsdoc module runs alongside these rules.

## Path aliases

A module reachable through a `compilerOptions.paths` alias also answers to the namespace that alias spells,
so a target may be written in either form. With `"@user/*": ["./src/*"]`, `./src/internal/hasher.ts`
declares into both `@acme/user/internal` and `@user/internal`. The shipped config passes the tsconfig it already
resolves for type-aware linting; point `tsConfigPath` elsewhere to read a different one, and leave it unset to switch
alias namespaces off. A name in `allowedInternals` always spells the `package.json` form.

## File-level docblock

The first docblock in a module is file-level when a blank line separates it from the code,
when the statement below it is an `import` or a re-export, or when a second docblock follows it.
An `@internal` there applies to every symbol the module exports.

```js
/**
 * @internal
 */

export class PasswordHasher {}
```

A tag on the symbol wins over it, and an explicit `@api` cancels an `@internal` further out
— that is how one public export escapes an internal module. A docblock carrying both tags is reported
by [`brnshkr/api-or-internal-tag`](./api-or-internal-tag.md); this rule reads it as `@internal`.

## What counts as a usage

Every reference is reported rather than the import that brought it in: calls, constructions, property and method
access, and type positions. Re-export specifiers and `export * from` are reported at the specifier,
since they have no reference to attach to. A property reached through a value the caller never imported counts too:

```js
// ❌ Bad — 'options.secret' is '@internal' where 'HashOptions' declares it
handle((options) => options.secret);
```

## Options

`allowedCallers` and `allowedInternals` widen what counts as a legal caller. An entry is a plain string,
a regular expression, or the delimited pattern string the PHP rule takes. A symbol name is the module
followed by `#` and the member path.

```js
// ./conf/eslint.mjs
import { getConfig } from '@brnshkr/config/eslint';

export default getConfig({
  rules: {
    'brnshkr/internal-usage': ['error', {
      allowedCallers: ['@acme/user/tests'],
      allowedInternals: [/^@acme\/shared/v, '@acme/user/internal/hasher#PasswordHasher'],
    }],
  },
});
```

An object entry bounds the exemption to the counterparts it names, and mixes with bare entries in one list:

```js
const allowedCallers = [
  // may reach any internal at all
  '@acme/console',
  // may reach only @acme/user's internals
  {
    '@acme/reporting': ['@acme/user'],
  },
];
```

## Differences from the PHP rule

- a module may always use the symbols it declares itself, because the module is the unit of encapsulation in JavaScript
- `/` is the namespace separator here, so a prefix carrying both a leading and a trailing
  separator reads as a delimited pattern instead
- a file-level docblock carrying `@internal` covers the whole module.
  A PHP file has no such fallback; there the class docblock covers its members
- a method or function carries `()` in its symbol name,
  matching how [`ResolvableDocReferenceRule`](../../../php/phpstan/rules/ResolvableDocReferenceRule.md)
  tells a method from a constant. An `allowedInternals` entry has to spell it the same way
- without type information the rule reads the imported module from disk. Only relative specifiers resolve that way,
  a property reached through a value is not checked, and a symbol name omits the `()` a declared function would carry
