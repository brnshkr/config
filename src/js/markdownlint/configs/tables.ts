/**
 * @internal @brnshkr/config/markdownlint
 */

import { resolveCustomRule } from '#markdownlint/utils/config.ts';
import { TABLE_STYLE } from '#markdownlint/utils/constants.ts';
import { MODULES, PACKAGES, resolvePackages } from '#markdownlint/utils/module.ts';

import type { Config } from '#markdownlint/types/config.ts';

export const tables = (): Config[] => {
  const {
    requiredAll: [isMarkdownlintRuleTableFormatInstalled],
  } = resolvePackages(MODULES.tables);

  if (!isMarkdownlintRuleTableFormatInstalled) {
    return [];
  }

  return [
    {
      customRules: [
        resolveCustomRule(PACKAGES.MARKDOWNLINT_RULE_TABLE_FORMAT),
      ],
      config: {
        'table-format': {
          style: TABLE_STYLE,
        },
      },
    },
  ];
};
