import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName, renameRules } from '../utils/config';
import { GLOB_SCRIPT_FILES } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const comments = async (): Promise<Config[]> => {
  const {
    requiredAll: [pluginComments],
  } = await resolvePackages(MODULES.comments);

  if (!pluginComments) {
    return [];
  }

  const recommendedConfig = pluginComments.configs.recommended;
  const recommendedRules = 'rules' in recommendedConfig ? recommendedConfig.rules : undefined;

  return [
    {
      name: buildConfigName(MAIN_SCOPES.COMMENTS, SUB_SCOPES.SETUP),
      plugins: {
        comments: pluginComments,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.COMMENTS, SUB_SCOPES.RULES),
      files: GLOB_SCRIPT_FILES,
      rules: {
        ...renameRules(recommendedRules, {
          '@eslint-community/eslint-comments': 'comments',
        }),
        'comments/no-unused-disable': 'error',
        'comments/require-description': 'error',
      },
    },
  ];
};
