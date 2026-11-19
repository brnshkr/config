/**
 * @internal @brnshkr/config/stylelint
 */

export const OVERRIDES = <const>{
  DEFENSIVE_FENCED_CODE_BLOCKS: 'defensive/fenced-code-blocks',
  FENCED_CODE: 'fenced-code',
  FENCED_CODE_BLOCKS: 'fenced-code-blocks',
  FENCED_CODE_RULE: 'fenced-code-rule',
  LESS: 'less',
  PHP_TEMPLATE: 'php-template',
  SCSS: 'scss',
  SVELTE: 'svelte',
  // eslint-disable-next-line no-warning-comments -- (vue-support)
  // TODO: This will be needed if and when Vue.js support is added
  // VUE: 'vue',
};

export type Override = typeof OVERRIDES[keyof typeof OVERRIDES];
