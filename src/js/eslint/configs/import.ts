import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName, renameRules } from '../utils/config';
import { GLOB_DEVELOPMENT_FILES, GLOB_SCRIPT_FILES } from '../utils/globs';
import { isModuleEnabled, MODULES, resolvePackages } from '../utils/module';

import type { ESLint } from 'eslint';
import type { Config } from '../types/config';

export const imports = async (): Promise<Config[]> => {
  const {
    requiredAny: [pluginImport, pluginAntfu],
    optional: [importResovlerTypescript],
  } = await resolvePackages(MODULES.import);

  const plugins: Config['plugins'] = {};
  const settings: Config['settings'] = {};
  let pluginImportRules: Config['rules'] = {};
  let pluginAntfuRules: Config['rules'] = {};

  if (pluginImport) {
    plugins['import'] = <ESLint.Plugin><unknown>pluginImport;

    settings['import-x/resolver-next'] = [
      pluginImport.createNodeResolver(),
      importResovlerTypescript === undefined
        ? undefined
        : importResovlerTypescript.createTypeScriptImportResolver({
          bun: true,
        }),
    ].filter(Boolean);

    const pluginImportTsRules: Config['rules'] = isModuleEnabled(MODULES.typescript)
      ? renameRules(pluginImport.flatConfigs.typescript.rules, { 'import-x': 'import' })
      : {};

    pluginImportRules = {
      ...renameRules(pluginImport.flatConfigs.recommended.rules, { 'import-x': 'import' }),
      ...pluginImportTsRules,
      'import/consistent-type-specifier-style': ['error', 'prefer-top-level'],
      'import/extensions': ['error', 'ignorePackages', {
        js: 'never',
        ts: 'never',
        cts: 'never',
        mts: 'never',
      }],
      'import/first': 'error',
      'import/max-dependencies': ['error', {
        max: 15,
      }],
      'import/newline-after-import': 'error',
      'import/no-absolute-path': 'error',
      'import/no-amd': 'error',
      'import/no-cycle': 'error',
      'import/no-default-export': 'error',
      'import/no-deprecated': 'error',
      'import/no-duplicates': 'error',
      'import/no-dynamic-require': 'error',
      'import/no-empty-named-blocks': 'error',
      'import/no-extraneous-dependencies': ['error', {
        devDependencies: GLOB_DEVELOPMENT_FILES,
      }],
      'import/no-import-module-exports': 'error',
      'import/no-mutable-exports': 'error',
      'import/no-named-as-default-member': 'error',
      'import/no-named-as-default': 'error',
      'import/no-named-default': 'error',
      'import/no-namespace': 'error',
      'import/no-relative-packages': 'error',
      'import/no-rename-default': 'error',
      'import/no-self-import': 'error',
      'import/no-unassigned-import': ['error', {
        allow: [
          '**/*.{css,scss}',
        ],
      }],
      'import/no-useless-path-segments': ['error', {
        noUselessIndex: true,
      }],
      'import/no-unresolved': ['error', {
        commonjs: true,
        caseSensitive: true,
      }],
      'import/no-webpack-loader-syntax': 'error',
      'import/order': [
        'error',
        {
          warnOnUnassignedImports: true,
          sortTypesGroup: true,
          consolidateIslands: 'inside-groups',
          'newlines-between': 'always-and-inside-groups',
          'newlines-between-types': 'never',
          named: {
            enabled: true,
            types: 'types-last',
          },
          alphabetize: {
            order: 'asc',
            caseInsensitive: true,
          },
          groups: [
            'builtin',
            'external',
            'unknown',
            'internal',
            'parent',
            'sibling',
            'index',
            'object',
            'type',
          ],
          pathGroups: [{
            pattern: '**/*.{css,scss}',
            group: 'object',
          }],
        },
      ],
      'import/unambiguous': 'error',
    };
  }

  if (pluginAntfu) {
    plugins['antfu'] = pluginAntfu;

    pluginAntfuRules = {
      'antfu/import-dedupe': 'error',
      'antfu/no-import-dist': 'error',
      'antfu/no-import-node-modules-by-path': 'error',
    };
  }

  if (Object.keys(plugins).length === 0) {
    return [];
  }

  const setupConfig: Config = {
    name: buildConfigName(MAIN_SCOPES.IMPORT, SUB_SCOPES.SETUP),
    plugins,
  };

  if (Object.keys(settings).length > 0) {
    setupConfig.settings = settings;
  }

  return [
    setupConfig,
    {
      name: buildConfigName(MAIN_SCOPES.IMPORT, SUB_SCOPES.RULES),
      files: GLOB_SCRIPT_FILES,
      rules: {
        ...pluginImportRules,
        ...pluginAntfuRules,
      },
    },
  ];
};
