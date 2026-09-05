/**
 * @internal @brnshkr/config/commitlint
 */

import { ERROR } from '../utils/constants';

import type { Config } from '../types/config';

const SCOPE_MIN_LENGTH = 2;
const SUBJECT_MIN_LENGTH = 5;

export const commit = (): Config[] => [
  {
    helpUrl: 'https://github.com/brnshkr/config/blob/master/README.md#-commit-style',
    rules: {
      'body-case': [ERROR, 'always', 'sentence-case'],
      'body-leading-blank': [ERROR, 'always'],
      // @ts-expect-error -- Upstream types this as a case rule, but the rule itself accepts no value
      'breaking-change-exclamation-mark': [ERROR, 'always'],
      'footer-leading-blank': [ERROR, 'always'],
      'header-case': [ERROR, 'always', 'lower-case'],
      'scope-case': [ERROR, 'always', 'lower-case'],
      'scope-delimiter-style': [ERROR, 'always', ['/']],
      'scope-empty': [ERROR, 'never'],
      'scope-min-length': [ERROR, 'always', SCOPE_MIN_LENGTH],
      'subject-min-length': [ERROR, 'always', SUBJECT_MIN_LENGTH],
    },
  },
];
