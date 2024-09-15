import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName, renameRules } from '../utils/config';
import { GLOB_SCRIPT_FILES } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';
import type { NodeOptions } from '../types/options';

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
        'node/no-missing-import': 'error',
        'node/no-mixed-requires': 'error',
        'node/no-new-require': 'error',
        'node/no-path-concat': 'error',
        'node/no-process-env': 'error',
        'node/no-sync': 'error',
        'node/prefer-global/buffer': 'error',
        'node/prefer-global/console': 'error',
        'node/prefer-global/process': 'error',
        'node/prefer-global/text-decoder': 'error',
        'node/prefer-global/text-encoder': 'error',
        'node/prefer-global/url-search-params': 'error',
        'node/prefer-global/url': 'error',
        'node/prefer-node-protocol': 'error',
        'node/prefer-promises/dns': 'error',
        'node/prefer-promises/fs': 'error',
      },
    },
  ];
};
