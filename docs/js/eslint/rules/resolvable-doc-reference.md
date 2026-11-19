# `brnshkr/resolvable-doc-reference` [🔍](../../../../src/js/eslint/configs/builtin/resolvable-doc-reference.ts 'Go to source')

JavaScript mirror of the PHP [`ResolvableDocReferenceRule`](../../../php/phpstan/rules/ResolvableDocReferenceRule.md),
which is the source of truth for the convention. A `@see` or `@link` target must name a symbol that exists,
so a reference that no longer points anywhere is reported instead of silently rotting.

```ts
import { Mailer } from './mailer';

// ❌ Bad — nothing named `Transport` exists here
/**
 * Hands the message to the transport.
 *
 * @see Transport
 */
export const forward = (): void => {};

// ✅ Good — reached through the import
/**
 * Hands the message to the transport.
 *
 * @see Mailer
 */
export const send = (): void => {};
```

Members are checked as well, through either separator, so a typo in `{@link Mailer.send}` or `{@link Mailer#send}`
is reported. Link text is ignored. Whether a member exists is all that is checked
— unlike PHP, TypeScript writes methods, properties and constants alike, so the rule cannot tell which kind you meant.

`@see` also takes free text and URLs, so a lowercase target under it is left alone.
`{@link}` accepts only a namepath, and is checked whatever the case.
