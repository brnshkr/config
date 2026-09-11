# `brnshkr/type-assertion-style` [🔍](../../../../src/js/eslint/configs/builtin/type-assertion-style.ts 'Go to source')

An operand that begins with a keyword must be parenthesized,
and every other operand must sit against the closing angle bracket with no space,
so an assertion reads the same way everywhere.
[`style/keyword-spacing`](https://eslint.style/rules/keyword-spacing) requires a space before a keyword that follows `>`,
and the parenthesis is what removes that requirement; without it the two rules reverse each other on every pass.
The autofix adds or removes the parentheses and closes the gap in one edit,
unless its range covers a comment that rewriting would delete.

```ts
// ❌ Bad — 'await' and 'new' are keywords, so the operand needs parentheses
const config = <Config> await load();
const service = <Service>new Service();

// ❌ Bad — 'price' is an identifier, so nothing separates it from the assertion
const total = <Total> price;

// ✅ Good
const config = <Config>(await load());
const service = <Service>(new Service());
const total = <Total>price;
```

A double assertion is reported on the inner one, which is the assertion holding the operand.

## Governed operands

The keywords are `await`, `typeof`, `void`, `delete`, `new`, `this`, `super`, `function`, `class` and `async function`.
`new.target` is excluded, because it is a property access that `keyword-spacing` does not report.
Calls, members, literals, `import()` and punctuation unaries such as `!value` are excluded for the same reason.

`<Type>` binds tighter than every operator and takes only the unary expression to its right,
so an identifier operand is left alone — all three forms below are valid, and each casts a different expression.

```ts
// ✅ Good — casts 'price', then adds tax
const total = <Total>price + tax;

// ✅ Good — the same cast, with its extent written out
const total = (<Total>price) + tax;

// ✅ Good — casts the sum
const total = <Total>(price + tax);
```

## Assertions written with `as`

`as` binds loosely, so `await load() as Config` already parses as `(await load()) as Config`
and the parentheses carry shape alone.
The rule applies them anyway, so the same form holds where a project sets
[`ts/consistent-type-assertions`](https://typescript-eslint.io/rules/consistent-type-assertions/) to `as`.
Spacing is not checked there, since `as` is separated by its own keyword spacing.

```ts
// ❌ Bad — the awaited operand carries no parentheses
const config = await load() as Config;

// ✅ Good
const config = (await load()) as Config;
```

## Options

- `parentheses` — `always` (default) requires them; `never` forbids them and removes redundant ones.
  A bare string in place of the object sets this one
- `spacing` — `never` (default) forbids a space after the closing angle bracket; `always` requires one.
  Angle-bracket assertions only

`never` removes the parenthesis that satisfies `style/keyword-spacing`,
so it needs either `spacing: 'always'` or that rule turned off.

```js
// ./conf/eslint.config.mjs
import { getConfig } from '@brnshkr/config/eslint';

export default getConfig({
  rules: {
    'brnshkr/type-assertion-style': ['error', {
      parentheses: 'never',
      spacing: 'always',
    }],
  },
});
```
