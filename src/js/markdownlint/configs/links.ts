/**
 * @internal @brnshkr/config/markdownlint
 */

import { resolveCustomRule } from '../utils/config';
import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

// NOTICE: the package declares no entry point at all, so only its rule file can be imported
const NO_TRAILING_SLASH_IN_LINKS_RULE = `${
  PACKAGES.MARKDOWNLINT_RULE_NO_TRAILING_SLASH_IN_LINKS
}/src/no-trailing-slash-in-links.js`;

export const links = (): Config[] => {
  const {
    requiredAny: [
      isMarkdownlintRuleRelativeLinksInstalled,
      isMarkdownlintRuleNoTrailingSlashInLinksInstalled,
    ],
  } = resolvePackages(MODULES.links);

  const customRules = [
    ...isMarkdownlintRuleRelativeLinksInstalled ? [PACKAGES.MARKDOWNLINT_RULE_RELATIVE_LINKS] : [],
    ...isMarkdownlintRuleNoTrailingSlashInLinksInstalled ? [NO_TRAILING_SLASH_IN_LINKS_RULE] : [],
  ];

  if (customRules.length === 0) {
    return [];
  }

  return [
    {
      customRules: customRules.map((id) => resolveCustomRule(id)),
      config: {
        ...isMarkdownlintRuleRelativeLinksInstalled && {
          'relative-links': {
            // eslint-disable-next-line ts/naming-convention -- Option needs to be cased like this
            root_path: '.',
          },
        },
      },
    },
  ];
};
