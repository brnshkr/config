/**
 * @internal @brnshkr/config/stylelint
 */

import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const logical = (): Config[] => {
  const {
    requiredAll: [isStylelintPluginLogicalCssInstalled],
  } = resolvePackages(MODULES.logical);

  if (!isStylelintPluginLogicalCssInstalled) {
    return [];
  }

  return [
    {
      plugins: PACKAGES.STYLELINT_PLUGIN_LOGICAL_CSS,
      extends: [`${PACKAGES.STYLELINT_PLUGIN_LOGICAL_CSS}/configs/recommended`],
    },
  ];
};
