/**
 * @internal @brnshkr/config/stylelint
 */

import { createModuleState, resolvePackagesSharedSynchronously } from '../../shared/utils/module';
import { STYLELINT_PACKAGES } from '../../shared/utils/package-resolvers';

import type { ModuleInfo, PackageResolver } from '../../shared/utils/module';
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

export const resolvePackages: PackageResolver<StylelintPackage> = resolvePackagesSharedSynchronously;
export const { isModuleEnabled, setModuleEnabled } = createModuleState();
