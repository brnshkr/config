/**
 * @internal @brnshkr/config/stylelint
 */

import { OVERRIDES } from '#stylelint/types/overrides.ts';
import { buildOverrideName } from '#stylelint/utils/config.ts';
import { MODULES, PACKAGES, resolvePackages } from '#stylelint/utils/module.ts';

import type { Config } from '#stylelint/types/config.ts';

export const less = (): Config[] => {
  const {
    requiredAll: [isStylelintConfigStandardLessInstalled],
  } = resolvePackages(MODULES.less);

  if (!isStylelintConfigStandardLessInstalled) {
    return [];
  }

  return [
    {
      overrides: [
        {
          name: buildOverrideName(OVERRIDES.LESS),
          files: ['**/*.less'],
          extends: PACKAGES.STYLELINT_CONFIG_STANDARD_LESS,
          rules: {
            // eslint-disable-next-line unicorn/no-null -- Null is required here
            'function-no-unknown': null,
            'less/color-hex-case': 'lower',
            'less/container-name-pattern': ['^(--)?([a-z][a-z0-9]*)(-[a-z0-9]+)*$'],
            'less/declaration-property-value-no-unknown': true,
          },
        },
      ],
    },
  ];
};
