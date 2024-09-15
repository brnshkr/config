import { RuleConfigSeverity } from '@commitlint/types';

const SCOPE_MIN_LENGTH = 2;
const SUBJECT_MIN_LENGTH = 5;

/**
 * @import * as $12$commitlint$l$types from '@commitlint/types';
 *
 * @type {$12$commitlint$l$types.UserConfig}
 */
// @ts-expect-error -- We cannot mark these array as readonly here, but Commitlint expects them to be readonly. This type annotation is just to help with autocompletion.
export default {
  helpUrl: 'https://github.com/brnshkr/config/blob/master/README.md#-commit-style',
  extends: [
    '@commitlint/config-conventional',
  ],
  rules: {
    'body-case': [RuleConfigSeverity.Error, 'always', 'sentence-case'],
    'body-full-stop': [RuleConfigSeverity.Error, 'never'],
    'breaking-change-exclamation-mark': [RuleConfigSeverity.Error, 'always'],
    'header-case': [RuleConfigSeverity.Error, 'always', 'lower-case'],
    'header-full-stop': [RuleConfigSeverity.Error, 'never'],
    'scope-case': [RuleConfigSeverity.Error, 'always', 'lower-case'],
    'scope-delimiter-style': [RuleConfigSeverity.Error, 'always', ['/', '-']],
    'scope-empty': [RuleConfigSeverity.Error, 'never'],
    'scope-min-length': [RuleConfigSeverity.Error, 'always', SCOPE_MIN_LENGTH],
    'subject-min-length': [RuleConfigSeverity.Error, 'always', SUBJECT_MIN_LENGTH],
  },
};
