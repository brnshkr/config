/**
 * @internal @brnshkr/config/eslint
 */

import { objectFromEntries, objectKeys } from '../../shared/utils/object';
import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { GLOB_SCRIPT_FILES } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

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
