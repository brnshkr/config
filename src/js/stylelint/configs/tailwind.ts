/**
 * @internal @brnshkr/config/stylelint
 */

import { isModuleEnabled, MODULES, resolvePackages } from '#stylelint/utils/module.ts';

import type { Config } from '#stylelint/types/config.ts';

export const tailwind = (): Config[] => {
  const {
    optional: [hasConfigStandard],
  } = resolvePackages(MODULES.css);

  return [
    {
      rules: {
        ...(hasConfigStandard
          ? {
            'import-notation': 'string',
          }
          : {}),
        ...(isModuleEnabled(MODULES.style)
          ? {
            // eslint-disable-next-line unicorn/no-null -- Null is required here
            '@stylistic/at-rule-semicolon-space-before': null,
          }
          : {}),
      },
    },
  ];
};
