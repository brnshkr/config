/**
 * @internal @brnshkr/config/markdownlint
 */

import { resolveCustomRule } from '../utils/config';
import { TABLE_STYLE } from '../utils/constants';
import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

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
