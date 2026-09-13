/**
 * @internal @brnshkr/config/vitest
 */

import { createModuleState, resolvePackagesSharedSynchronously } from '../../shared/utils/module';
import { VITEST_PACKAGES } from '../../shared/utils/package-resolvers';

import type { ModuleInfo, PackageResolver } from '../../shared/utils/module';
import type { VitestPackage } from '../../shared/utils/package-resolvers';
import type { configs } from '../configs';

export { VITEST_PACKAGES as PACKAGES } from '../../shared/utils/package-resolvers';

export const MODULES = <const>{
  environment: {
    name: 'environment',
    packages: {
      requiredAny: [
        VITEST_PACKAGES.HAPPY_DOM,
        VITEST_PACKAGES.JSDOM,
      ],
    },
  },
  spelling: {
    name: 'spelling',
  },
  ui: {
    name: 'ui',
    packages: {
      requiredAll: [
        VITEST_PACKAGES.VITEST_UI,
      ],
    },
  },
} satisfies Partial<Record<keyof typeof configs, ModuleInfo<readonly VitestPackage[]>>>;

export const resolvePackages: PackageResolver<VitestPackage> = resolvePackagesSharedSynchronously;
export const { isModuleEnabled, setModuleEnabled } = createModuleState();
