import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { GLOB_SCRIPT_FILES } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const perfectionist = async (): Promise<Config[]> => {
  const {
    requiredAll: [pluginPerfectionist],
  } = await resolvePackages(MODULES.perfectionist);

  if (!pluginPerfectionist) {
    return [];
  }

  return [
    {
      name: buildConfigName(MAIN_SCOPES.PERFECTIONIST, SUB_SCOPES.SETUP),
      plugins: {
        perfectionist: pluginPerfectionist,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.PERFECTIONIST, SUB_SCOPES.RULES),
      files: GLOB_SCRIPT_FILES,
      rules: {
        'perfectionist/sort-array-includes': 'error',
        'perfectionist/sort-heritage-clauses': 'error',
        'perfectionist/sort-maps': 'error',
        'perfectionist/sort-named-exports': 'error',
        'perfectionist/sort-sets': 'error',
        'perfectionist/sort-switch-case': 'error',
      },
    },
  ];
};
