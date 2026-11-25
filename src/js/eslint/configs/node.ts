/**
 * @internal @brnshkr/config/eslint
 */

import { MAIN_SCOPES, SUB_SCOPES } from '#eslint/types/scopes.ts';
import { buildConfigName, renameRules } from '#eslint/utils/config.ts';
import { GLOB_SCRIPT_FILES } from '#eslint/utils/globs.ts';

import {
  isModuleEnabled,
  MODULES,
  PACKAGES,
  resolvePackages,
} from '#eslint/utils/module.ts';

import { doAllPackagesExist } from '#shared/utils/module.ts';

import type { Config } from '#eslint/types/config.ts';
import type { NodeOptions } from '#eslint/types/options.ts';

export const node = async (options?: Partial<NodeOptions>): Promise<Config[]> => {
  const {
    requiredAll: [pluginNode],
  } = await resolvePackages(MODULES.node);

  if (!pluginNode) {
    return [];
  }

  return [
    {
      name: buildConfigName(MAIN_SCOPES.NODE, SUB_SCOPES.SETUP),
      plugins: {
        node: pluginNode,
      },
      settings: {
        n: {
          version: options?.version ?? process.versions.node,
        },
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.NODE, SUB_SCOPES.RULES),
      files: GLOB_SCRIPT_FILES,
      rules: {
        ...renameRules(pluginNode.configs['flat/recommended'].rules, { n: 'node' }),
        'node/exports-style': 'error',
        'node/global-require': 'error',
        'node/handle-callback-err': 'error',
        'node/no-mixed-requires': 'error',
        'node/no-new-require': 'error',
        'node/no-path-concat': 'error',
        'node/no-process-env': 'error',
        'node/no-sync': 'error',
        'node/no-unpublished-bin': 'error',
        'node/prefer-global/buffer': 'error',
        'node/prefer-global/console': 'error',
        'node/prefer-global/crypto': 'error',
        'node/prefer-global/process': 'error',
        'node/prefer-global/text-decoder': 'error',
        'node/prefer-global/text-encoder': 'error',
        'node/prefer-global/timers': 'error',
        'node/prefer-global/url-search-params': 'error',
        'node/prefer-global/url': 'error',
        'node/prefer-import/assert-strict': 'error',
        'node/prefer-node-protocol': 'error',
        'node/prefer-process-get-builtin-module': 'error',
        'node/prefer-promises/dns': 'error',
        'node/prefer-promises/fs': 'error',
        ...((isModuleEnabled(MODULES.import) && doAllPackagesExist([PACKAGES.ESLINT_PLUGIN_IMPORT_X]))
          ? {
            'node/no-extraneous-import': 'off',
            'node/no-extraneous-require': 'off',
          }
          : undefined),
      },
    },
  ];
};
