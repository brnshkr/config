import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

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
