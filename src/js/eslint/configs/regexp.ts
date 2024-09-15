import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { GLOB_SCRIPT_FILES } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const regexp = async (): Promise<Config[]> => {
  const {
    requiredAll: [pluginRegExp],
  } = await resolvePackages(MODULES.regexp);

  if (!pluginRegExp) {
    return [];
  }

  return [
    {
      name: buildConfigName(MAIN_SCOPES.REGEXP, SUB_SCOPES.SETUP),
      plugins: {
        regexp: pluginRegExp,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.REGEXP, SUB_SCOPES.RULES),
      files: GLOB_SCRIPT_FILES,
      rules: {
        ...pluginRegExp.configs.recommended.rules,
        'regexp/confusing-quantifier': 'error',
        'regexp/grapheme-string-literal': 'error',
        'regexp/letter-case': ['error', {
          caseInsensitive: 'lowercase',
          unicodeEscape: 'lowercase',
          hexadecimalEscape: 'lowercase',
          controlEscape: 'uppercase',
        }],
        'regexp/no-control-character': 'error',
        'regexp/no-empty-alternative': 'error',
        'regexp/no-lazy-ends': 'error',
        'regexp/no-octal': 'error',
        'regexp/no-potentially-useless-backreference': 'error',
        'regexp/no-standalone-backslash': 'error',
        'regexp/no-super-linear-move': 'error',
        'regexp/no-useless-flag': 'error',
        'regexp/optimal-lookaround-quantifier': 'error',
        'regexp/prefer-escape-replacement-dollar-char': 'error',
        'regexp/prefer-lookaround': 'error',
        'regexp/prefer-named-backreference': 'error',
        'regexp/prefer-named-capture-group': 'error',
        'regexp/prefer-named-replacement': 'error',
        'regexp/prefer-quantifier': 'error',
        'regexp/prefer-regexp-exec': 'error',
        'regexp/prefer-regexp-test': 'error',
        'regexp/prefer-result-array-groups': 'error',
        'regexp/require-unicode-sets-regexp': 'error',
        'regexp/sort-alternatives': 'error',
        'regexp/sort-character-class-elements': 'error',
        'regexp/unicode-escape': 'error',
        'regexp/unicode-property': ['error', {
          generalCategory: 'never',
          key: 'short',
          property: 'long',
        }],
      },
    },
  ];
};
