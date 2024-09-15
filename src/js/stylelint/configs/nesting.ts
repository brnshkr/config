import {
  isModuleEnabled,
  MODULES,
  PACKAGES,
  resolvePackages,
} from '../utils/module';

import type { Config } from '../types/config';

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
