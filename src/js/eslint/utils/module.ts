import { isModuleEnabledByDefault, resolvePackagesSharedAsynchronously } from '../../shared/utils/module';
import { packageOrganization } from '../../shared/utils/package-json';
import { ESLINT_PACKAGES } from '../../shared/utils/package-resolvers';

import type { Maybe } from '../../shared/types/core';
import type { ModuleInfo, ResolvedPackages } from '../../shared/utils/module';
import type { EslintPackage } from '../../shared/utils/package-resolvers';
import type { configs } from '../configs';

export { ESLINT_PACKAGES as PACKAGES } from '../../shared/utils/package-resolvers';

export const MODULES = <const>{
  [packageOrganization]: {
    name: packageOrganization,
  },
  comments: {
    name: 'comments',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_PLUGIN_ESLINT_COMMENTS,
      ],
    },
  },
  css: {
    name: 'css',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_CSS,
      ],
    },
  },
  gitignore: {
    name: 'gitignore',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_FLAT_CONFIG_GITIGNORE,
      ],
    },
  },
  import: {
    name: 'import',
    packages: {
      requiredAny: [
        ESLINT_PACKAGES.ESLINT_PLUGIN_IMPORT_X,
        ESLINT_PACKAGES.ESLINT_PLUGIN_ANTFU,
      ],
      optional: [
        ESLINT_PACKAGES.ESLINT_IMPORT_RESOVLER_TYPESCRIPT,
      ],
    },
  },
  javascript: {
    name: 'javascript',
    packages: {
      optional: [
        ESLINT_PACKAGES.ESLINT_PLUGIN_ANTFU,
        ESLINT_PACKAGES.ESLINT_PLUGIN_UNUSED_IMPORTS,
      ],
    },
  },
  jsdoc: {
    name: 'jsdoc',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_PLUGIN_JSDOC,
      ],
      optional: [
        ESLINT_PACKAGES.ESLINT_PLUGIN_JSDOC_PROCESSOR,
      ],
    },
  },
  json: {
    name: 'json',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_JSON,
      ],
      optional: [
        ESLINT_PACKAGES.ESLINT_PLUGIN_JSONC,
      ],
    },
  },
  markdown: {
    name: 'markdown',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_MARKDOWN,
        ESLINT_PACKAGES.ESLINT_MERGE_PROCESSORS,
      ],
    },
  },
  node: {
    name: 'node',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_PLUGIN_N,
      ],
    },
  },
  perfectionist: {
    name: 'perfectionist',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_PLUGIN_PERFECTIONIST,
      ],
    },
  },
  regexp: {
    name: 'regexp',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_PLUGIN_REGEXP,
      ],
    },
  },
  style: {
    name: 'style',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_PLUGIN_STYLISTIC,
      ],
    },
  },
  svelte: {
    name: 'svelte',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.SVELTE,
        ESLINT_PACKAGES.ESLINT_PLUGIN_SVELTE,
      ],
    },
  },
  test: {
    name: 'test',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.VITEST_ESLINT_PLUGIN,
      ],
    },
  },
  toml: {
    name: 'toml',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_PLUGIN_TOML,
      ],
    },
  },
  typescript: {
    name: 'typescript',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.TYPESCRIPT,
        ESLINT_PACKAGES.TYPESCRIPT_ESLINT,
      ],
    },
  },
  unicorn: {
    name: 'unicorn',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_PLUGIN_UNICORN,
      ],
    },
  },
  yaml: {
    name: 'yaml',
    packages: {
      requiredAll: [
        ESLINT_PACKAGES.ESLINT_PLUGIN_YML,
      ],
    },
  },
} satisfies Partial<Record<keyof typeof configs, ModuleInfo<readonly EslintPackage[]>>>;

export const resolvePackages = async <
  TModuleInfo extends ModuleInfo<readonly EslintPackage[]>,
  TType extends Maybe<keyof TModuleInfo['packages']> = undefined,
>(
  moduleInfo: TModuleInfo,
  type?: TType,
): Promise<ResolvedPackages<TModuleInfo, TType>> => resolvePackagesSharedAsynchronously(moduleInfo, type);

const enabledStates: Record<string, boolean> = {};

export const isModuleEnabled = (moduleInfo: ModuleInfo): boolean => enabledStates[moduleInfo.name]
  ?? isModuleEnabledByDefault(moduleInfo);

export const setModuleEnabled = (moduleInfo: ModuleInfo, isEnabled: boolean): void => {
  enabledStates[moduleInfo.name] = isEnabled;
};
