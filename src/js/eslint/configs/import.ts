/**
 * @internal @brnshkr/config/eslint
 */

import { objectKeys } from '../../shared/utils/object';
import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName, renameRules } from '../utils/config';
import { GLOB_DEVELOPMENT_FILES, GLOB_SCRIPT_FILES, GLOB_TS } from '../utils/globs';
import { isModuleEnabled, MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const imports = async (): Promise<Config[]> => {
  const {
    requiredAny: [pluginImport, pluginAntfu],
    optional: [importResolverTypescript],
  } = await resolvePackages(MODULES.import);

  const plugins: Config['plugins'] = {};
  const settings: Config['settings'] = {};
  let pluginImportRules: Config['rules'] = {};
  let pluginImportTsRules: Config['rules'] = {};
  let pluginAntfuRules: Config['rules'] = {};

  if (pluginImport) {
    plugins['import'] = pluginImport;

    settings['import-x/resolver-next'] = [
      pluginImport.createNodeResolver(),
      importResolverTypescript === undefined
        ? undefined
        : importResolverTypescript.createTypeScriptImportResolver({
          bun: true,
        }),
    ].filter(Boolean);

    if (isModuleEnabled(MODULES.typescript)) {
      settings['import-x/extensions'] = [
        '.cjs',
        '.cts',
        '.js',
        '.jsx',
        '.mjs',
        '.mts',
        '.ts',
        '.tsx',
      ];
    }

    settings['import-x/ignore'] = [
      String.raw`[/\\]node_modules[/\\]`,
    ];

    settings['import-x/core-modules'] = [
      'bun',
      'bun:bundle',
      'bun:ffi',
      'bun:jsc',
      'bun:sqlite',
      'bun:test',
    ];

    pluginImportTsRules = renameRules(pluginImport.flatConfigs.typescript.rules, { 'import-x': 'import' });

    pluginImportRules = {
      ...renameRules(pluginImport.flatConfigs.recommended.rules, { 'import-x': 'import' }),
      'import/consistent-type-specifier-style': 'error',
      'import/extensions': ['error', 'ignorePackages', {
        checkTypeImports: true,
        pattern: {
          js: 'never',
          ts: 'never',
          cts: 'never',
          mts: 'never',
        },
        pathGroupOverrides: [
          {
            pattern: '{{.,..,../..,../../..,../../../..,../../../../..}/**/declarations,$types/declarations,$declarations}{,/**}',
            action: 'ignore',
          },
        ],
      }],
      'import/first': 'error',
      'import/max-dependencies': ['error', {
        max: 15,
      }],
      'import/namespace': 'off',
      'import/newline-after-import': 'error',
      'import/no-absolute-path': 'error',
      'import/no-amd': 'error',
      'import/no-cycle': ['error', {
        ignoreExternal: true,
        maxDepth: 3,
      }],
      'import/no-default-export': 'error',
      'import/no-deprecated': 'error',
      'import/no-duplicates': 'error',
      'import/no-dynamic-require': 'error',
      'import/no-empty-named-blocks': 'error',
      'import/no-extraneous-dependencies': ['error', {
        devDependencies: GLOB_DEVELOPMENT_FILES,
        includeTypes: true,
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
        caseSensitiveStrict: true,
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

  if (objectKeys(plugins).length === 0) {
    return [];
  }

  const setupConfig: Config = {
    name: buildConfigName(MAIN_SCOPES.IMPORT, SUB_SCOPES.SETUP),
    plugins,
  };

  if (objectKeys(settings).length > 0) {
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
    ...(isModuleEnabled(MODULES.typescript)
      ? [{
        name: buildConfigName(MAIN_SCOPES.IMPORT, `${SUB_SCOPES.RULES}-typescript`),
        files: [GLOB_TS],
        rules: pluginImportTsRules,
      } satisfies Config]
      : []),
  ];
};
