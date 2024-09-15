import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const order = (): Config[] => {
  const {
    requiredAll: [isStylelintConfigRecessOrderInstalled],
  } = resolvePackages(MODULES.order);

  if (!isStylelintConfigRecessOrderInstalled) {
    return [];
  }

  return [
    {
      extends: PACKAGES.STYLELINT_CONFIG_RECESS_ORDER,
    },
  ];
};
