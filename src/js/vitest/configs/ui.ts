/**
 * @internal @brnshkr/config/vitest
 */

import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

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
