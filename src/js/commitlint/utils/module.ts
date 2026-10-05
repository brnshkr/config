/**
 * @internal @brnshkr/config/commitlint
 */

import { createModuleState, resolvePackagesSharedSynchronously } from '#shared/utils/module.ts';
import { COMMITLINT_PACKAGES } from '#shared/utils/package-resolvers.ts';

import type { configs } from '#commitlint/configs/index.ts';
import type { ModuleInfo, PackageResolver } from '#shared/utils/module.ts';
import type { CommitlintPackage } from '#shared/utils/package-resolvers.ts';

export { COMMITLINT_PACKAGES as PACKAGES } from '#shared/utils/package-resolvers.ts';

export const MODULES = <const>{
  conventional: {
    name: 'conventional',
    packages: {
      requiredAll: [
        COMMITLINT_PACKAGES.COMMITLINT_CONFIG_CONVENTIONAL,
      ],
    },
  },
  functions: {
    name: 'functions',
    packages: {
      requiredAll: [
        COMMITLINT_PACKAGES.COMMITLINT_PLUGIN_FUNCTION_RULES,
      ],
    },
  },
  tense: {
    name: 'tense',
    packages: {
      requiredAll: [
        COMMITLINT_PACKAGES.COMMITLINT_PLUGIN_TENSE,
      ],
    },
  },
} satisfies Partial<Record<keyof typeof configs, ModuleInfo<readonly CommitlintPackage[]>>>;

export const resolvePackages: PackageResolver<CommitlintPackage> = resolvePackagesSharedSynchronously;
export const { isModuleEnabled, setModuleEnabled } = createModuleState();
