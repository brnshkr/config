/**
 * @internal @brnshkr/config/commitlint
 */

import { MODULES, PACKAGES, resolvePackages } from '#commitlint/utils/module.ts';

import type { Config } from '#commitlint/types/config.ts';

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
