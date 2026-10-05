/**
 * @internal @brnshkr/config/eslint
 */

import { MAIN_SCOPES, SUB_SCOPES } from '#eslint/types/scopes.ts';
import { buildConfigName } from '#eslint/utils/config.ts';
import { GLOB_SCRIPT_FILES } from '#eslint/utils/globs.ts';
import { MODULES, resolvePackages } from '#eslint/utils/module.ts';

import type { Config } from '#eslint/types/config.ts';

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
