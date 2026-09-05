/**
 * @internal @brnshkr/config/commitlint
 */

import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const functions = (): Config[] => {
  const {
    requiredAll: [isCommitlintPluginFunctionRulesInstalled],
  } = resolvePackages(MODULES.functions);

  if (!isCommitlintPluginFunctionRulesInstalled) {
    return [];
  }

  return [
    {
      plugins: [
        PACKAGES.COMMITLINT_PLUGIN_FUNCTION_RULES,
      ],
    },
  ];
};
