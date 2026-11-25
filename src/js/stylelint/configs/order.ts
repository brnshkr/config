/**
 * @internal @brnshkr/config/stylelint
 */

import { MODULES, PACKAGES, resolvePackages } from '#stylelint/utils/module.ts';

import type { Config } from '#stylelint/types/config.ts';

export const order = (): Config[] => {
  const {
    requiredAll: [isStylelintConfigRecessOrderInstalled],
  } = resolvePackages(MODULES.order);

  if (!isStylelintConfigRecessOrderInstalled) {
    return [];
  }

  return [
    {
      extends: PACKAGES.STYLELINT_CONFIG_RECESS_ORDER,
    },
  ];
};
