import path from 'node:path';

import { RuleTester } from 'eslint';
import tseslint from 'typescript-eslint';

import type { Config } from '../../../src/js/eslint/types/config';

export const TYPE_AWARE_FIXTURE_ROOT = path.resolve(import.meta.dirname, '../fixtures/type-aware');
export const TYPE_AWARE_FIXTURE_FILE = path.join(TYPE_AWARE_FIXTURE_ROOT, 'file.ts');

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

export const typeAwareRuleTester = new RuleTester({
  ...COMMON_OPTIONS,
  languageOptions: {
    ...COMMON_OPTIONS.languageOptions,
    parser: tseslint.parser,
    parserOptions: {
      ...COMMON_OPTIONS.languageOptions.parserOptions,
      projectService: {
        allowDefaultProject: ['*.ts'],
      },
      tsconfigRootDir: TYPE_AWARE_FIXTURE_ROOT,
    },
  },
});
