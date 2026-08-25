/**
 * @internal @brnshkr/config/stylelint
 */

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
      extends: `${PACKAGES.STYLELINT_PLUGIN_DEFENSIVE_CSS}/configs/strict`,
      rules: {
        'defensive-css/require-custom-property-fallback': true,
        'defensive-css/require-pure-selectors': [true, {
          ignoreElements: ['*', 'html', 'body'],
          ignoreAttributeSelectors: true,
          strict: true,
        }],
      },
    },
  ];
};
