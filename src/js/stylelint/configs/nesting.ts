/**
 * @internal @brnshkr/config/stylelint
 */

import {
  isModuleEnabled,
  MODULES,
  PACKAGES,
  resolvePackages,
} from '#stylelint/utils/module.ts';

import type { Config } from '#stylelint/types/config.ts';

export const nesting = (): Config[] => {
  const {
    requiredAll: [isStylelintUseNestingInstalled],
  } = resolvePackages(MODULES.nesting);

  if (!isStylelintUseNestingInstalled) {
    return [];
  }

  return [
    {
      plugins: PACKAGES.STYLELINT_USE_NESTING,
      rules: {
        'csstools/use-nesting': ['always', {
          syntax: isModuleEnabled(MODULES.scss) ? 'scss' : 'css',
        }],
      },
    },
  ];
};
