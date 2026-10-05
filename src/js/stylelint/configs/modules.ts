/**
 * @internal @brnshkr/config/stylelint
 */

import { MODULES, PACKAGES, resolvePackages } from '#stylelint/utils/module.ts';

import type { Config } from '#stylelint/types/config.ts';

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
