import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const modules = (): Config[] => {
  const {
    requiredAll: [isStylelintConfigCssModulesInstalled],
  } = resolvePackages(MODULES.modules);

  if (!isStylelintConfigCssModulesInstalled) {
    return [];
  }

  return [
    {
      extends: PACKAGES.STYLELINT_CONFIG_CSS_MODULES,
    },
  ];
};
