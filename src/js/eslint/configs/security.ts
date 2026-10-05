/**
 * @internal @brnshkr/config/eslint
 */

import { MAIN_SCOPES, SUB_SCOPES } from '#eslint/types/scopes.ts';
import { buildConfigName } from '#eslint/utils/config.ts';
import { GLOB_SCRIPT_FILES } from '#eslint/utils/globs.ts';
import { MODULES, resolvePackages } from '#eslint/utils/module.ts';
import { objectFromEntries, objectKeys } from '#shared/utils/object.ts';

import type { Config } from '#eslint/types/config.ts';

export const security = async (): Promise<Config[]> => {
  const {
    requiredAll: [pluginSecurity],
  } = await resolvePackages(MODULES.security);

  if (!pluginSecurity) {
    return [];
  }

  return [
    {
      name: buildConfigName(MAIN_SCOPES.SECURITY, SUB_SCOPES.SETUP),
      plugins: {
        security: pluginSecurity,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.SECURITY, SUB_SCOPES.RULES),
      files: GLOB_SCRIPT_FILES,
      rules: objectFromEntries(
        objectKeys(pluginSecurity.configs.recommended.rules ?? {}).map((rule) => [rule, <const>'error']),
      ),
    },
  ];
};
