/**
 * @internal @brnshkr/config/eslint
 */

import { INDENT, QUOTES } from '../../shared/utils/constants';
import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName, renameRules } from '../utils/config';
import { GLOB_YAML } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const yaml = async (): Promise<Config[]> => {
  const {
    requiredAll: [pluginYaml],
  } = await resolvePackages(MODULES.yaml);

  if (!pluginYaml) {
    return [];
  }

  return [
    {
      name: buildConfigName(MAIN_SCOPES.YAML, SUB_SCOPES.SETUP),
      plugins: {
        yaml: pluginYaml,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.YAML, SUB_SCOPES.RULES),
      files: [GLOB_YAML],
      language: 'yaml/yaml',
      rules: {
        /* eslint-disable no-magic-numbers -- Index 2 refers the config containing the rules of the standard config here */
        ...renameRules(pluginYaml.configs.standard[2]?.rules, { yml: 'yaml' }),
        /* eslint-enable no-magic-numbers -- Restore rule */
        'yaml/block-mapping-colon-indicator-newline': ['error', 'never'],
        'yaml/file-extension': 'error',
        'yaml/flow-mapping-curly-spacing': ['error', 'always', {
          emptyObjects: 'never',
        }],
        'yaml/indent': ['error', INDENT],
        'yaml/no-multiple-empty-lines': 'error',
        'yaml/no-trailing-zeros': 'error',
        'yaml/quotes': ['error', {
          avoidEscape: true,
          prefer: QUOTES,
        }],
        'yaml/require-string-key': 'error',
      },
    },
  ];
};
