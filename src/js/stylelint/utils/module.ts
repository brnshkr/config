/**
 * @internal @brnshkr/config/stylelint
 */

import { createModuleState, resolvePackagesSharedSynchronously } from '#shared/utils/module.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';
import { ESLINT_PACKAGES, STYLELINT_PACKAGES } from '#shared/utils/package-resolvers.ts';

import type { ModuleInfo, PackageResolver } from '#shared/utils/module.ts';
import type { StylelintPackage } from '#shared/utils/package-resolvers.ts';
import type { configs } from '#stylelint/configs/index.ts';

export { STYLELINT_PACKAGES as PACKAGES } from '#shared/utils/package-resolvers.ts';

export const MODULES = <const>{
  [packageOrganization]: {
    name: packageOrganization,
  },
  baseline: {
    name: 'baseline',
    packages: {
      requiredAll: [
        STYLELINT_PACKAGES.STYLELINT_PLUGIN_USE_BASELINE,
      ],
    },
  },
  css: {
    name: 'css',
    packages: {
      optional: [
        STYLELINT_PACKAGES.STYLELINT_CONFIG_STANDARD,
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
  less: {
    name: 'less',
    packages: {
      requiredAll: [
        STYLELINT_PACKAGES.STYLELINT_CONFIG_STANDARD_LESS,
      ],
    },
  },
  markdown: {
    name: 'markdown',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_MARKDOWN,
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
  tailwind: {
    name: 'tailwind',
    packages: {
      requiredAll: [
        STYLELINT_PACKAGES.TAILWINDCSS,
      ],
    },
  },
} satisfies Partial<Record<
  keyof typeof configs,
  ModuleInfo<readonly (StylelintPackage | typeof ESLINT_PACKAGES.ESLINT_MARKDOWN)[]>
>>;

// eslint-disable-next-line security/detect-object-injection -- The key is the package's own organization name
export const BUILTIN_MODULE = MODULES[packageOrganization];
export const resolvePackages: PackageResolver<StylelintPackage> = resolvePackagesSharedSynchronously;
export const { isModuleEnabled, setModuleEnabled } = createModuleState();
