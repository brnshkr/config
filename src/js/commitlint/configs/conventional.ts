/**
 * @internal @brnshkr/config/commitlint
 */

import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

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
