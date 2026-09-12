/**
 * @internal @brnshkr/config/vitest
 */

import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

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
