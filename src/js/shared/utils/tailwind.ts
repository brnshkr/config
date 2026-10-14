/**
 * @internal @brnshkr/config
 */

import type { CssDialect } from '#shared/utils/dialect.ts';

export const TAILWIND_DIALECT = <const>{
  atRules: {
    apply: {
      prelude: '<any-value>',
    },
    config: {
      prelude: '<string>',
    },
    'custom-variant': {
      prelude: '<any-value>',
      isScopingRoot: true,
    },
    plugin: {
      prelude: '<string>',
    },
    reference: {
      prelude: '<string>',
    },
    slot: {},
    source: {
      prelude: '<any-value>',
    },
    theme: {
      prelude: '<any-value>?',
    },
    utility: {
      prelude: '<any-value>',
      isDeclarationBlock: true,
      isScopingRoot: true,
    },
    variant: {
      prelude: '<any-value>',
      isDeclarationBlock: true,
      isScopingRoot: true,
    },
  },
  functions: {
    '--alpha()': '--alpha( <color> / <percentage> )',
    '--spacing()': '--spacing( <number> )',
  },
  types: {
    'length-percentage': '<length> | <percentage> | <--spacing()>',
  },
} satisfies Partial<CssDialect>;
