/**
 * @internal @brnshkr/config/commitlint
 */

import { MODULES, PACKAGES, resolvePackages } from '#commitlint/utils/module.ts';

import type { Config } from '#commitlint/types/config.ts';

export const conventional = (): Config[] => {
  const {
    requiredAll: [isCommitlintConfigConventionalInstalled],
  } = resolvePackages(MODULES.conventional);

  if (!isCommitlintConfigConventionalInstalled) {
    return [];
  }

  return [
    {
      extends: PACKAGES.COMMITLINT_CONFIG_CONVENTIONAL,
    },
  ];
};
