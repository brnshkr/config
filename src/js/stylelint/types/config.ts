import type { Config as StylelintConfig } from 'stylelint';

export type Config = {
  [Key in keyof StylelintConfig]: StylelintConfig[Key];
};
