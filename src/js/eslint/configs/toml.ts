import { INDENT } from '../../shared/utils/constants';
import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { GLOB_TOML } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { ESLint } from 'eslint';
import type { Config } from '../types/config';

export const toml = async (): Promise<Config[]> => {
  const {
    requiredAll: [pluginToml, parserToml],
  } = await resolvePackages(MODULES.toml);

  if (!pluginToml || !parserToml) {
    return [];
  }

  return [
    {
      name: buildConfigName(MAIN_SCOPES.TOML, SUB_SCOPES.SETUP),
      plugins: {
        toml: <ESLint.Plugin>pluginToml,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.TOML, SUB_SCOPES.PARSER),
      files: [GLOB_TOML],
      languageOptions: {
        parser: parserToml,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.TOML, SUB_SCOPES.RULES),
      files: [GLOB_TOML],
      language: 'toml/toml',
      rules: {
        /* eslint-disable no-magic-numbers -- Index 2 refers the config containing the rules of the standard config here */
        ...pluginToml.configs.standard[2]?.rules,
        /* eslint-enable no-magic-numbers -- Restore rule */
        'toml/array-bracket-spacing': ['error', 'never'],
        'toml/indent': ['error', INDENT],
        'toml/no-mixed-type-in-array': 'error',
      },
    },
  ];
};
