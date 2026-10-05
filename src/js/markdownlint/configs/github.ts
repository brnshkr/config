/**
 * @internal @brnshkr/config/markdownlint
 */

import { resolveCustomRule } from '#markdownlint/utils/config.ts';
import { MODULES, PACKAGES, resolvePackages } from '#markdownlint/utils/module.ts';

import type { Config } from '#markdownlint/types/config.ts';

export const github = (): Config[] => {
  const {
    requiredAll: [isMarkdownlintGithubInstalled],
  } = resolvePackages(MODULES.github);

  if (!isMarkdownlintGithubInstalled) {
    return [];
  }

  return [
    {
      customRules: [
        resolveCustomRule(PACKAGES.MARKDOWNLINT_GITHUB),
      ],
      config: {
        'no-empty-alt-text': true,
      },
    },
  ];
};
