import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const defensive = (): Config[] => {
  const {
    requiredAll: [isStylelintPluginDefensiveCssInstalled],
  } = resolvePackages(MODULES.defensive);

  if (!isStylelintPluginDefensiveCssInstalled) {
    return [];
  }

  return [
    {
      plugins: PACKAGES.STYLELINT_PLUGIN_DEFENSIVE_CSS,
      rules: {
        'plugin/use-defensive-css': [true, {
          'accidental-hover': true,
          'background-repeat': true,
          'flex-wrapping': true,
          'scroll-chaining': true,
          'scrollbar-gutter': true,
          'vendor-prefix-grouping': true,
        }],
      },
    },
  ];
};
