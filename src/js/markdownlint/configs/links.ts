/**
 * @internal @brnshkr/config/markdownlint
 */

import { resolveCustomRule } from '#markdownlint/utils/config.ts';
import { MODULES, PACKAGES, resolvePackages } from '#markdownlint/utils/module.ts';

import type { Config } from '#markdownlint/types/config.ts';

export const links = (): Config[] => {
  const {
    requiredAll: [isMarkdownlintRuleRelativeLinksInstalled],
  } = resolvePackages(MODULES.links);

  if (!isMarkdownlintRuleRelativeLinksInstalled) {
    return [];
  }

  return [
    {
      customRules: [
        resolveCustomRule(PACKAGES.MARKDOWNLINT_RULE_RELATIVE_LINKS),
      ],
      config: {
        'relative-links': {
          // eslint-disable-next-line ts/naming-convention -- Option needs to be cased like this
          root_path: '.',
        },
      },
    },
  ];
};
