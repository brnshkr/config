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
      rules: {
        'plugin/use-logical-properties-and-values': true,
        'plugin/use-logical-units': true,
      },
    },
  ];
};
