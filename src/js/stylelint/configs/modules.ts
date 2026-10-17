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

export const isCssModulesActive = (): boolean => {
  if (!isModuleEnabled(MODULES.modules)) {
    return false;
  }

  const {
    requiredAll: [isStylelintConfigCssModulesInstalled],
  } = resolvePackages(MODULES.modules);

  return isStylelintConfigCssModulesInstalled;
};

export const modules = (): Config[] => (isCssModulesActive()
  ? [
    {
      extends: PACKAGES.STYLELINT_CONFIG_CSS_MODULES,
    },
  ]
  : []);
