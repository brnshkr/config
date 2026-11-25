/**
 * @internal @brnshkr/config/markdownlint
 */

import { noPhpOpenTagRule } from '#markdownlint/configs/builtin/no-php-open-tag.ts';
import { noWarningCommentsRule } from '#markdownlint/configs/builtin/no-warning-comments.ts';
import { objectFromEntries, objectKeys, objectValues } from '#shared/utils/object.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';

import type { Config } from '#markdownlint/types/config.ts';

export const RULE_DEFINITIONS = <const>{
  'no-php-open-tag': noPhpOpenTagRule,
  'no-warning-comments': noWarningCommentsRule,
};

export const builtin = (): Config[] => [
  {
    customRules: objectValues(RULE_DEFINITIONS),
    config: objectFromEntries(
      objectKeys(RULE_DEFINITIONS).map((ruleName) => [<const>`${packageOrganization}/${ruleName}`, true]),
    ),
  },
];
