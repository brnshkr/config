import { isModuleEnabledByDefault, resolvePackagesSharedSynchronously } from '../../shared/utils/module';
import { STYLELINT_PACKAGES } from '../../shared/utils/package-resolvers';

import type { Maybe } from '../../shared/types/core';
import type { ModuleInfo, ResolvedPackages } from '../../shared/utils/module';
import type { StylelintPackage } from '../../shared/utils/package-resolvers';
import type { configs } from '../configs';

export { STYLELINT_PACKAGES as PACKAGES } from '../../shared/utils/package-resolvers';

export const MODULES = <const>{
  baseline: {
    name: 'baseline',
    packages: {
      requiredAll: [
        STYLELINT_PACKAGES.STYLELINT_PLUGIN_USE_BASELINE,
      ],
    },
  },
  defensive: {
    name: 'defensive',
    packages: {
      requiredAll: [
        STYLELINT_PACKAGES.STYLELINT_PLUGIN_DEFENSIVE_CSS,
      ],
    },
  },
  html: {
    name: 'html',
    packages: {
      requiredAll: [
        STYLELINT_PACKAGES.POSTCSS_HTML,
        STYLELINT_PACKAGES.STYLELINT_CONFIG_HTML,
      ],
    },
  },
  logical: {
    name: 'logical',
    packages: {
      requiredAll: [
        STYLELINT_PACKAGES.STYLELINT_PLUGIN_LOGICAL_CSS,
      ],
    },
  },
  modules: {
    name: 'modules',
    packages: {
      requiredAll: [
        STYLELINT_PACKAGES.STYLELINT_CONFIG_CSS_MODULES,
      ],
    },
  },
  nesting: {
    name: 'nesting',
    packages: {
      requiredAll: [
        STYLELINT_PACKAGES.STYLELINT_USE_NESTING,
      ],
    },
  },
  order: {
    name: 'order',
    packages: {
      requiredAll: [
        STYLELINT_PACKAGES.STYLELINT_ORDER,
        STYLELINT_PACKAGES.STYLELINT_CONFIG_RECESS_ORDER,
      ],
    },
  },
  scss: {
    name: 'scss',
    packages: {
      requiredAll: [
        STYLELINT_PACKAGES.STYLELINT_CONFIG_STANDARD_SCSS,
      ],
      optional: [
        STYLELINT_PACKAGES.STYLELINT_PLUGIN_USE_BASELINE,
      ],
    },
  },
  strict: {
    name: 'strict',
    packages: {
      requiredAll: [
        STYLELINT_PACKAGES.STYLELINT_DECLARATION_STRICT_VALUE,
      ],
    },
  },
  style: {
    name: 'style',
    packages: {
      requiredAll: [
        STYLELINT_PACKAGES.STYLISTIC_STYLELINT_CONFIG,
      ],
    },
  },
} satisfies Partial<Record<keyof typeof configs, ModuleInfo<readonly StylelintPackage[]>>>;

export const resolvePackages = <
  TModuleInfo extends ModuleInfo<readonly StylelintPackage[]>,
  TType extends Maybe<keyof TModuleInfo['packages']> = undefined,
>(
  moduleInfo: TModuleInfo,
  type?: TType,
): ResolvedPackages<TModuleInfo, TType> => resolvePackagesSharedSynchronously(moduleInfo, type);

const enabledStates: Record<string, boolean> = {};

export const isModuleEnabled = (moduleInfo: ModuleInfo): boolean => enabledStates[moduleInfo.name]
  ?? isModuleEnabledByDefault(moduleInfo);

export const setModuleEnabled = (moduleInfo: ModuleInfo, isEnabled: boolean): void => {
  enabledStates[moduleInfo.name] = isEnabled;
};
