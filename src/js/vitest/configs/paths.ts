/**
 * @internal @brnshkr/config/vitest
 */

import {
  LOADERS,
  MODULES,
  PACKAGES,
  resolvePackages,
} from '../utils/module';

import type { Config } from '../types/config';

export const paths = (): Config[] => {
  const {
    requiredAll: [isViteTsconfigPathsInstalled],
  } = resolvePackages(MODULES.paths);

  if (!isViteTsconfigPathsInstalled) {
    return [];
  }

  return [
    {
      plugins: [
        LOADERS[PACKAGES.VITE_TSCONFIG_PATHS]().then((viteTsconfigPaths) => viteTsconfigPaths()),
      ],
    },
  ];
};
