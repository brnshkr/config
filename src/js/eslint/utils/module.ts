import { isPackageExists } from 'local-pkg';

import { interopImport } from '../../shared/utils/interop-import';
import { isModuleEnabledByDefault, resolvePackagesSharedAsynchronously } from '../../shared/utils/module';
import { packageOrganization } from '../../shared/utils/package-json';

import type { Maybe } from '../../shared/types/core';
import type { ModuleInfo, ResolvedPackages } from '../../shared/utils/module';
import type { configs } from '../configs';

export const PACKAGES = <const>{
  ESLINT_CSS: '@eslint/css',
  ESLINT_FLAT_CONFIG_GITIGNORE: 'eslint-config-flat-gitignore',
  ESLINT_IMPORT_RESOVLER_TYPESCRIPT: 'eslint-import-resolver-typescript',
  ESLINT_JSON: '@eslint/json',
  ESLINT_MARKDOWN: '@eslint/markdown',
  ESLINT_MERGE_PROCESSORS: 'eslint-merge-processors',
  ESLINT_PLUGIN_ANTFU: 'eslint-plugin-antfu',
  ESLINT_PLUGIN_ESLINT_COMMENTS: '@eslint-community/eslint-plugin-eslint-comments',
  ESLINT_PLUGIN_IMPORT_X: 'eslint-plugin-import-x',
  ESLINT_PLUGIN_JSDOC: 'eslint-plugin-jsdoc',
  ESLINT_PLUGIN_JSDOC_PROCESSOR: 'eslint-plugin-jsdoc/getJsdocProcessorPlugin.js',
  ESLINT_PLUGIN_JSONC: 'eslint-plugin-jsonc',
  ESLINT_PLUGIN_N: 'eslint-plugin-n',
  ESLINT_PLUGIN_PERFECTIONIST: 'eslint-plugin-perfectionist',
  ESLINT_PLUGIN_REGEXP: 'eslint-plugin-regexp',
  ESLINT_PLUGIN_STYLISTIC: '@stylistic/eslint-plugin',
  ESLINT_PLUGIN_SVELTE: 'eslint-plugin-svelte',
  ESLINT_PLUGIN_TOML: 'eslint-plugin-toml',
  ESLINT_PLUGIN_UNICORN: 'eslint-plugin-unicorn',
  ESLINT_PLUGIN_UNUSED_IMPORTS: 'eslint-plugin-unused-imports',
  ESLINT_PLUGIN_YML: 'eslint-plugin-yml',
  JSONC_ESLINT_PARSER: 'jsonc-eslint-parser',
  SVELTE: 'svelte',
  TOML_ESLINT_PARSER: 'toml-eslint-parser',
  TYPESCRIPT: 'typescript',
  TYPESCRIPT_ESLINT: 'typescript-eslint',
  VITEST_ESLINT_PLUGIN: '@vitest/eslint-plugin',
  YAML_ESLINT_PARSER: 'yaml-eslint-parser',
};

type Package = typeof PACKAGES[keyof typeof PACKAGES];

// NOTICE: Package names must be duplicated here to allow for type inference of dynamic imports
export const PACKAGE_RESOLVERS = <const>{
  [PACKAGES.ESLINT_CSS]: async () => await interopImport(
    import('@eslint/css'),
  ),
  [PACKAGES.ESLINT_FLAT_CONFIG_GITIGNORE]: async () => await interopImport(
    import('eslint-config-flat-gitignore'),
  ),
  [PACKAGES.ESLINT_JSON]: async () => await interopImport(
    import('@eslint/json'),
  ),
  [PACKAGES.ESLINT_IMPORT_RESOVLER_TYPESCRIPT]: async () => await interopImport(
    import('eslint-import-resolver-typescript'),
  ),
  [PACKAGES.ESLINT_PLUGIN_ANTFU]: async () => await interopImport(
    import('eslint-plugin-antfu'),
  ),
  [PACKAGES.ESLINT_PLUGIN_ESLINT_COMMENTS]: async () => await interopImport(
    import('@eslint-community/eslint-plugin-eslint-comments'),
  ),
  [PACKAGES.ESLINT_PLUGIN_IMPORT_X]: async () => await interopImport(
    import('eslint-plugin-import-x'),
  ),
  [PACKAGES.ESLINT_PLUGIN_JSDOC]: async () => await interopImport(
    import('eslint-plugin-jsdoc'),
  ),
  [PACKAGES.ESLINT_PLUGIN_JSONC]: async () => await interopImport(
    import('eslint-plugin-jsonc'),
  ),
  [PACKAGES.ESLINT_PLUGIN_JSDOC_PROCESSOR]: async () => await interopImport(
    import('eslint-plugin-jsdoc/getJsdocProcessorPlugin.js'),
  ),
  [PACKAGES.ESLINT_MARKDOWN]: async () => await interopImport(
    import('@eslint/markdown'),
  ),
  [PACKAGES.ESLINT_MERGE_PROCESSORS]: async () => await interopImport(
    import('eslint-merge-processors'),
  ),
  [PACKAGES.ESLINT_PLUGIN_N]: async () => await interopImport(
    import('eslint-plugin-n'),
  ),
  [PACKAGES.ESLINT_PLUGIN_PERFECTIONIST]: async () => await interopImport(
    import('eslint-plugin-perfectionist'),
  ),
  [PACKAGES.ESLINT_PLUGIN_REGEXP]: async () => await interopImport(
    import('eslint-plugin-regexp'),
  ),
  [PACKAGES.ESLINT_PLUGIN_STYLISTIC]: async () => await interopImport(
    import('@stylistic/eslint-plugin'),
  ),
  [PACKAGES.ESLINT_PLUGIN_SVELTE]: async () => await interopImport(
    import('eslint-plugin-svelte'),
  ),
  [PACKAGES.ESLINT_PLUGIN_TOML]: async () => await interopImport(
    import('eslint-plugin-toml'),
  ),
  [PACKAGES.ESLINT_PLUGIN_UNICORN]: async () => await interopImport(
    import('eslint-plugin-unicorn'),
  ),
  [PACKAGES.ESLINT_PLUGIN_UNUSED_IMPORTS]: async () => await interopImport(
    import('eslint-plugin-unused-imports'),
  ),
  [PACKAGES.ESLINT_PLUGIN_YML]: async () => await interopImport(
    import('eslint-plugin-yml'),
  ),
  [PACKAGES.JSONC_ESLINT_PARSER]: async () => await interopImport(
    import('jsonc-eslint-parser'),
  ),
  [PACKAGES.TOML_ESLINT_PARSER]: async () => await interopImport(
    import('toml-eslint-parser'),
  ),
  // Do not import, just check for existence
  [PACKAGES.SVELTE]: () => isPackageExists(PACKAGES.SVELTE),
  // Do not import, just check for existence
  [PACKAGES.TYPESCRIPT]: () => isPackageExists(PACKAGES.TYPESCRIPT),
  [PACKAGES.TYPESCRIPT_ESLINT]: async () => await interopImport(
    import('typescript-eslint'),
  ),
  [PACKAGES.VITEST_ESLINT_PLUGIN]: async () => await interopImport(
    import('@vitest/eslint-plugin'),
  ),
  [PACKAGES.YAML_ESLINT_PARSER]: async () => await interopImport(
    import('yaml-eslint-parser'),
  ),
} satisfies Record<Package, (() => Promise<unknown>) | (() => boolean)>;

export const MODULES = <const>{
  [packageOrganization]: {
    name: packageOrganization,
  },
  comments: {
    name: 'comments',
    packages: {
      requiredAll: [
        PACKAGES.ESLINT_PLUGIN_ESLINT_COMMENTS,
      ],
    },
  },
  css: {
    name: 'css',
    packages: {
      requiredAll: [
        PACKAGES.ESLINT_CSS,
      ],
    },
  },
  gitignore: {
    name: 'gitignore',
    packages: {
      requiredAll: [
        PACKAGES.ESLINT_FLAT_CONFIG_GITIGNORE,
      ],
    },
  },
  import: {
    name: 'import',
    packages: {
      requiredAny: [
        PACKAGES.ESLINT_PLUGIN_IMPORT_X,
        PACKAGES.ESLINT_PLUGIN_ANTFU,
      ],
      optional: [
        PACKAGES.ESLINT_IMPORT_RESOVLER_TYPESCRIPT,
      ],
    },
  },
  javascript: {
    name: 'javascript',
    packages: {
      optional: [
        PACKAGES.ESLINT_PLUGIN_ANTFU,
        PACKAGES.ESLINT_PLUGIN_UNUSED_IMPORTS,
      ],
    },
  },
  jsdoc: {
    name: 'jsdoc',
    packages: {
      requiredAll: [
        PACKAGES.ESLINT_PLUGIN_JSDOC,
      ],
      optional: [
        PACKAGES.ESLINT_PLUGIN_JSDOC_PROCESSOR,
      ],
    },
  },
  json: {
    name: 'json',
    packages: {
      requiredAll: [
        PACKAGES.ESLINT_JSON,
      ],
      optional: [
        PACKAGES.ESLINT_PLUGIN_JSONC,
        PACKAGES.JSONC_ESLINT_PARSER,
      ],
    },
  },
  markdown: {
    name: 'markdown',
    packages: {
      requiredAll: [
        PACKAGES.ESLINT_MARKDOWN,
        PACKAGES.ESLINT_MERGE_PROCESSORS,
      ],
    },
  },
  node: {
    name: 'node',
    packages: {
      requiredAll: [
        PACKAGES.ESLINT_PLUGIN_N,
      ],
    },
  },
  perfectionist: {
    name: 'perfectionist',
    packages: {
      requiredAll: [
        PACKAGES.ESLINT_PLUGIN_PERFECTIONIST,
      ],
    },
  },
  regexp: {
    name: 'regexp',
    packages: {
      requiredAll: [
        PACKAGES.ESLINT_PLUGIN_REGEXP,
      ],
    },
  },
  style: {
    name: 'style',
    packages: {
      requiredAll: [
        PACKAGES.ESLINT_PLUGIN_STYLISTIC,
      ],
    },
  },
  svelte: {
    name: 'svelte',
    packages: {
      requiredAll: [
        PACKAGES.SVELTE,
        PACKAGES.ESLINT_PLUGIN_SVELTE,
      ],
    },
  },
  test: {
    name: 'test',
    packages: {
      requiredAll: [
        PACKAGES.VITEST_ESLINT_PLUGIN,
      ],
    },
  },
  toml: {
    name: 'toml',
    packages: {
      requiredAll: [
        PACKAGES.ESLINT_PLUGIN_TOML,
        PACKAGES.TOML_ESLINT_PARSER,
      ],
    },
  },
  typescript: {
    name: 'typescript',
    packages: {
      requiredAll: [
        PACKAGES.TYPESCRIPT,
        PACKAGES.TYPESCRIPT_ESLINT,
      ],
    },
  },
  unicorn: {
    name: 'unicorn',
    packages: {
      requiredAll: [
        PACKAGES.ESLINT_PLUGIN_UNICORN,
      ],
    },
  },
  yaml: {
    name: 'yaml',
    packages: {
      requiredAll: [
        PACKAGES.ESLINT_PLUGIN_YML,
        PACKAGES.YAML_ESLINT_PARSER,
      ],
    },
  },
} satisfies Partial<Record<keyof typeof configs, ModuleInfo<readonly Package[]>>>;

export const resolvePackages = async <
  TModuleInfo extends ModuleInfo<readonly Package[]>,
  TType extends Maybe<keyof TModuleInfo['packages']> = undefined,
>(
  moduleInfo: TModuleInfo,
  type?: TType,
): Promise<ResolvedPackages<TModuleInfo, TType>> => resolvePackagesSharedAsynchronously(moduleInfo, type);

const enabledStates: Record<string, boolean> = {};

export const isModuleEnabled = (moduleInfo: ModuleInfo): boolean => enabledStates[moduleInfo.name]
  ?? isModuleEnabledByDefault(moduleInfo);

export const setModuleEnabled = (moduleInfo: ModuleInfo, state: boolean): void => {
  enabledStates[moduleInfo.name] = state;
};
