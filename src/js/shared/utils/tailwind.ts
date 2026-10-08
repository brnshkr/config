/**
 * @internal @brnshkr/config
 */

export const TAILWIND_AT_RULE_PRELUDES = <const>{
  apply: '<any-value>',
  config: '<string>',
  'custom-variant': '<any-value>',
  plugin: '<string>',
  reference: '<string>',
  // eslint-disable-next-line unicorn/no-null -- The CSS syntax definition requires an explicit null to mark an at-rule as prelude-less
  slot: null,
  source: '<any-value>',
  theme: '<any-value>?',
  utility: '<any-value>',
  variant: '<any-value>',
};

export const TAILWIND_SCOPING_AT_RULES = <const>[
  'custom-variant',
  'utility',
  'variant',
] satisfies string[];
