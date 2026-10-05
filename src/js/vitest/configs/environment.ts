/**
 * @internal @brnshkr/config/vitest
 */

import { MODULES, PACKAGES, resolvePackages } from '#vitest/utils/module.ts';

import type { Config } from '#vitest/types/config.ts';

export const environment = (): Config[] => {
  const {
    requiredAny: [isHappyDomInstalled, isJsdomInstalled],
  } = resolvePackages(MODULES.environment);

  if (!isHappyDomInstalled && !isJsdomInstalled) {
    return [];
  }

  return [
    {
      test: {
        environment: isHappyDomInstalled
          ? PACKAGES.HAPPY_DOM
          : PACKAGES.JSDOM,
      },
    },
  ];
};
