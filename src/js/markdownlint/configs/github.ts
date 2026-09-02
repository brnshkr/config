/**
 * @internal @brnshkr/config/markdownlint
 */

import { resolveCustomRule } from '../utils/config';
import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

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
