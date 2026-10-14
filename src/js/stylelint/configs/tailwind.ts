/**
 * @internal @brnshkr/config/stylelint
 */

import { MODULES, resolvePackages } from '#stylelint/utils/module.ts';

import type { Config } from '#stylelint/types/config.ts';

export const tailwind = (): Config[] => {
  const {
    optional: [hasConfigStandard],
  } = resolvePackages(MODULES.css);

  return hasConfigStandard
    ? [
      {
        rules: {
          'import-notation': 'string',
        },
      },
    ]
    : [];
};
