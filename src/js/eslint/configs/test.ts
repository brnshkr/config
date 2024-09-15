import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName, renameRules } from '../utils/config';
import { GLOB_TEST_FILES } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const test = async (): Promise<Config[]> => {
  const {
    requiredAll: [pluginVitest],
  } = await resolvePackages(MODULES.test);

  if (!pluginVitest) {
    return [];
  }

  return [
    {
      name: buildConfigName(MAIN_SCOPES.TEST, SUB_SCOPES.SETUP),
      plugins: {
        test: pluginVitest,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.TEST, SUB_SCOPES.RULES),
      files: GLOB_TEST_FILES,
      rules: {
        ...renameRules(pluginVitest.configs.recommended.rules, { vitest: 'test' }),
        'test/consistent-each-for': ['error', {
          test: 'each',
          it: 'each',
          describe: 'each',
          suite: 'each',
        }],
        'test/consistent-test-filename': 'error',
        'test/consistent-test-it': 'error',
        'test/consistent-vitest-vi': 'error',
        'test/hoisted-apis-on-top': 'error',
        'test/no-alias-methods': 'error',
        'test/no-conditional-in-test': 'error',
        'test/no-conditional-tests': 'error',
        'test/no-duplicate-hooks': 'error',
        'test/no-test-prefixes': 'error',
        'test/no-test-return-statement': 'error',
        'test/padding-around-all': 'error',
        'test/prefer-called-once': 'error',
        'test/prefer-called-with': 'error',
        'test/prefer-comparison-matcher': 'error',
        'test/prefer-each': 'error',
        'test/prefer-equality-matcher': 'error',
        'test/prefer-expect-resolves': 'error',
        'test/prefer-expect-type-of': 'error',
        'test/prefer-hooks-in-order': 'error',
        'test/prefer-hooks-on-top': 'error',
        'test/prefer-import-in-mock': 'error',
        'test/prefer-importing-vitest-globals': 'error',
        'test/prefer-lowercase-title': 'error',
        'test/prefer-mock-promise-shorthand': 'error',
        'test/prefer-snapshot-hint': 'error',
        'test/prefer-spy-on': 'error',
        'test/prefer-strict-boolean-matchers': 'error',
        'test/prefer-strict-equal': 'error',
        'test/prefer-to-be-object': 'error',
        'test/prefer-to-be': 'error',
        'test/prefer-to-contain': 'error',
        'test/prefer-to-have-length': 'error',
        'test/prefer-todo': 'error',
        'test/prefer-vi-mocked': 'error',
        'test/require-awaited-expect-poll': 'error',
        'test/require-hook': 'error',
        'test/require-mock-type-parameters': 'error',
        'test/warn-todo': 'error',
      },
    },
  ];
};
