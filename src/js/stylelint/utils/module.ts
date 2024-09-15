import { isPackageExists } from 'local-pkg';

import { isModuleEnabledByDefault, resolvePackagesSharedSynchronously } from '../../shared/utils/module';

import type { Maybe } from '../../shared/types/core';
import type { ModuleInfo, ResolvedPackages } from '../../shared/utils/module';
import type { configs } from '../configs';

export const PACKAGES = <const>{
  POSTCSS_HTML: 'postcss-html',
  STYLELINT_CONFIG_CSS_MODULES: 'stylelint-config-css-modules',
  STYLELINT_CONFIG_HTML: 'stylelint-config-html',
  STYLELINT_CONFIG_RECESS_ORDER: 'stylelint-config-recess-order',
  STYLELINT_CONFIG_STANDARD_SCSS: 'stylelint-config-standard-scss',
  STYLELINT_DECLARATION_STRICT_VALUE: 'stylelint-declaration-strict-value',
  STYLELINT_ORDER: 'stylelint-order',
  STYLELINT_PLUGIN_DEFENSIVE_CSS: 'stylelint-plugin-defensive-css',
  STYLELINT_PLUGIN_LOGICAL_CSS: 'stylelint-plugin-logical-css',
  STYLELINT_PLUGIN_USE_BASELINE: 'stylelint-plugin-use-baseline',
  STYLELINT_USE_NESTING: 'stylelint-use-nesting',
  STYLISTIC_STYLELINT_CONFIG: '@stylistic/stylelint-config',
};

type Package = typeof PACKAGES[keyof typeof PACKAGES];

export const PACKAGE_RESOLVERS = <const>{
  [PACKAGES.POSTCSS_HTML]: () => isPackageExists(PACKAGES.POSTCSS_HTML),
  [PACKAGES.STYLELINT_CONFIG_CSS_MODULES]: () => isPackageExists(PACKAGES.STYLELINT_CONFIG_CSS_MODULES),
  [PACKAGES.STYLELINT_CONFIG_HTML]: () => isPackageExists(PACKAGES.STYLELINT_CONFIG_HTML),
  [PACKAGES.STYLELINT_CONFIG_RECESS_ORDER]: () => isPackageExists(PACKAGES.STYLELINT_CONFIG_RECESS_ORDER),
  [PACKAGES.STYLELINT_CONFIG_STANDARD_SCSS]: () => isPackageExists(PACKAGES.STYLELINT_CONFIG_STANDARD_SCSS),
  [PACKAGES.STYLELINT_DECLARATION_STRICT_VALUE]: () => isPackageExists(PACKAGES.STYLELINT_DECLARATION_STRICT_VALUE),
  [PACKAGES.STYLELINT_ORDER]: () => isPackageExists(PACKAGES.STYLELINT_ORDER),
  [PACKAGES.STYLELINT_PLUGIN_DEFENSIVE_CSS]: () => isPackageExists(PACKAGES.STYLELINT_PLUGIN_DEFENSIVE_CSS),
  [PACKAGES.STYLELINT_PLUGIN_LOGICAL_CSS]: () => isPackageExists(PACKAGES.STYLELINT_PLUGIN_LOGICAL_CSS),
  [PACKAGES.STYLELINT_PLUGIN_USE_BASELINE]: () => isPackageExists(PACKAGES.STYLELINT_PLUGIN_USE_BASELINE),
  [PACKAGES.STYLELINT_USE_NESTING]: () => isPackageExists(PACKAGES.STYLELINT_USE_NESTING),
  [PACKAGES.STYLISTIC_STYLELINT_CONFIG]: () => isPackageExists(PACKAGES.STYLISTIC_STYLELINT_CONFIG),
} satisfies Record<Package, () => boolean>;

export const MODULES = <const>{
  baseline: {
    name: 'baseline',
    packages: {
      requiredAll: [
        PACKAGES.STYLELINT_PLUGIN_USE_BASELINE,
      ],
    },
  },
  defensive: {
    name: 'defensive',
    packages: {
      requiredAll: [
        PACKAGES.STYLELINT_PLUGIN_DEFENSIVE_CSS,
      ],
    },
  },
  html: {
    name: 'html',
    packages: {
      requiredAll: [
        PACKAGES.POSTCSS_HTML,
        PACKAGES.STYLELINT_CONFIG_HTML,
      ],
    },
  },
  logical: {
    name: 'logical',
    packages: {
      requiredAll: [
        PACKAGES.STYLELINT_PLUGIN_LOGICAL_CSS,
      ],
    },
  },
  modules: {
    name: 'modules',
    packages: {
      requiredAll: [
        PACKAGES.STYLELINT_CONFIG_CSS_MODULES,
      ],
    },
  },
  nesting: {
    name: 'nesting',
    packages: {
      requiredAll: [
        PACKAGES.STYLELINT_USE_NESTING,
      ],
    },
  },
  order: {
    name: 'order',
    packages: {
      requiredAll: [
        PACKAGES.STYLELINT_ORDER,
        PACKAGES.STYLELINT_CONFIG_RECESS_ORDER,
      ],
    },
  },
  scss: {
    name: 'scss',
    packages: {
      requiredAll: [
        PACKAGES.STYLELINT_CONFIG_STANDARD_SCSS,
      ],
    },
  },
  strict: {
    name: 'strict',
    packages: {
      requiredAll: [
        PACKAGES.STYLELINT_DECLARATION_STRICT_VALUE,
      ],
    },
  },
  style: {
    name: 'style',
    packages: {
      requiredAll: [
        PACKAGES.STYLISTIC_STYLELINT_CONFIG,
      ],
    },
  },
} satisfies Partial<Record<keyof typeof configs, ModuleInfo<readonly Package[]>>>;

export const resolvePackages = <
  TModuleInfo extends ModuleInfo<readonly Package[]>,
  TType extends Maybe<keyof TModuleInfo['packages']> = undefined,
>(
  moduleInfo: TModuleInfo,
  type?: TType,
): ResolvedPackages<TModuleInfo, TType> => resolvePackagesSharedSynchronously(moduleInfo, type);

const enabledStates: Record<string, boolean> = {};

export const isModuleEnabled = (moduleInfo: ModuleInfo): boolean => enabledStates[moduleInfo.name]
  ?? isModuleEnabledByDefault(moduleInfo);

export const setModuleEnabled = (moduleInfo: ModuleInfo, state: boolean): void => {
  enabledStates[moduleInfo.name] = state;
};
