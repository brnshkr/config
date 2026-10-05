/**
 * @internal @brnshkr/config/vitest
 */

import { MODULES, resolvePackages } from '#vitest/utils/module.ts';

import type { Config } from '#vitest/types/config.ts';

export const ui = (): Config[] => {
  const {
    requiredAll: [isVitestUiInstalled],
  } = resolvePackages(MODULES.ui);

  if (!isVitestUiInstalled) {
    return [];
  }

  return [
    {
      test: {
        open: false,
      },
    },
  ];
};
