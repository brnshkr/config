/**
 * @internal @brnshkr/config/markdownlint
 */

import { noWarningCommentsRule } from '#markdownlint/configs/builtin/no-warning-comments.ts';
import { objectValues } from '#shared/utils/object.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';

import type { Rule } from 'markdownlint';
import type { Config } from '#markdownlint/types/config.ts';

export const RULE_DEFINITIONS: Readonly<Record<'no-warning-comments', Rule>> = {
  'no-warning-comments': noWarningCommentsRule,
};

export const builtin = (): Config[] => [
  {
    customRules: objectValues(RULE_DEFINITIONS),
    config: {
      [<const>`${packageOrganization}/no-warning-comments`]: true,
    },
  },
];
