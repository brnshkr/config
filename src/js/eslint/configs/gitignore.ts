import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';
import type { GitignoreOptions } from '../types/options';

export const gitignore = async (options?: Partial<GitignoreOptions>): Promise<Config[]> => {
  const {
    requiredAll: [pluginGitignore],
  } = await resolvePackages(MODULES.gitignore);

  if (!pluginGitignore) {
    return [];
  }

  return [
    pluginGitignore({
      name: buildConfigName(MAIN_SCOPES.IGNORES, SUB_SCOPES.GIT),
      ...options,
    }),
  ];
};
