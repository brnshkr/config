/**
 * @internal @brnshkr/config/eslint
 */

import { MAIN_SCOPES, SUB_SCOPES } from '#eslint/types/scopes.ts';
import { buildConfigName, renameRules } from '#eslint/utils/config.ts';
import { GLOB_SCRIPT_FILES } from '#eslint/utils/globs.ts';
import { MODULES, resolvePackages } from '#eslint/utils/module.ts';

import type { Config } from '#eslint/types/config.ts';

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
        'comments/require-description': ['error', {
          additionalDirectives: [
            '@ts-expect-error',
            'c8 ignore',
            'istanbul ignore',
            'node:coverage ignore',
            'svelte-ignore',
            'v8 ignore',
          ],
        }],
      },
    },
  ];
};
