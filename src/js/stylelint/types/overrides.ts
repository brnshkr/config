export const OVERRIDES = <const>{
  SCSS: 'scss',
  SVELTE: 'svelte',
  // eslint-disable-next-line no-warning-comments -- (vue-support)
  // TODO: This will be needed if and when vue support is added
  // VUE: 'vue',
};

export type Override = typeof OVERRIDES[keyof typeof OVERRIDES];
