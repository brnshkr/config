/**
 * @internal @brnshkr/config/stylelint
 */

import { GLOB_FENCED_CODE_BLOCKS } from '#stylelint/configs/builtin/fenced-code.ts';
import { OVERRIDES } from '#stylelint/types/overrides.ts';
import { buildOverrideName } from '#stylelint/utils/config.ts';
import { MODULES, PACKAGES, resolvePackages } from '#stylelint/utils/module.ts';

import type { Config } from '#stylelint/types/config.ts';

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
      overrides: [
        {
          name: buildOverrideName(OVERRIDES.DEFENSIVE_FENCED_CODE_BLOCKS),
          files: [GLOB_FENCED_CODE_BLOCKS],
          rules: {
            // eslint-disable-next-line unicorn/no-null -- Null is required here
            'defensive-css/require-at-layer': null,
          },
        },
      ],
    },
  ];
};
