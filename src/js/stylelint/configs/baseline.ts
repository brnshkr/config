/**
 * @internal @brnshkr/config/stylelint
 */

import { MODULES, PACKAGES, resolvePackages } from '#stylelint/utils/module.ts';

import type { Config } from '#stylelint/types/config.ts';

export const baseline = (): Config[] => {
  const {
    requiredAll: [isStylelintPluginUseBaselineInstalled],
  } = resolvePackages(MODULES.baseline);

  if (!isStylelintPluginUseBaselineInstalled) {
    return [];
  }

  return [
    {
      plugins: PACKAGES.STYLELINT_PLUGIN_USE_BASELINE,
      rules: {
        'plugin/use-baseline': true,
      },
    },
  ];
};
