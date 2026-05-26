import { RuleTester } from 'eslint';
import tseslint from 'typescript-eslint';

import type { Config } from '../../../src/js/eslint/types/config';

const COMMON_OPTIONS = <const>{
  languageOptions: {
    parserOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
    },
  },
} satisfies Config;

export const jsRuleTester = new RuleTester({
  ...COMMON_OPTIONS,
  languageOptions: {
    ...COMMON_OPTIONS.languageOptions,
  },
});

export const tsRuleTester = new RuleTester({
  ...COMMON_OPTIONS,
  languageOptions: {
    ...COMMON_OPTIONS.languageOptions,
    parser: tseslint.parser,
  },
});
