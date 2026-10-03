/**
 * @internal @brnshkr/config/markdownlint
 */

import { objectValues } from '../../../shared/utils/object';
import { packageOrganization } from '../../../shared/utils/package-json';

import { noWarningCommentsRule } from './no-warning-comments';

import type { Rule } from 'markdownlint';
import type { Config } from '../../types/config';

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
