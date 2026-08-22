import path from 'node:path';

import { RuleTester } from 'eslint';
import tseslint from 'typescript-eslint';

import { RULE_DEFINITIONS } from '../../../src/js/eslint/configs/builtin';
import { objectEntries } from '../../../src/js/shared/utils/object';

import type { RuleDefinition } from '../../../src/js/eslint/configs/builtin';
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

export const createTypeAwareRuleTester = (
  fixtureRoot: string,
  allowedDefaultProjectPaths: string[] = ['*.ts'],
): RuleTester => new RuleTester({
  ...COMMON_OPTIONS,
  languageOptions: {
    ...COMMON_OPTIONS.languageOptions,
    parser: tseslint.parser,
    parserOptions: {
      ...COMMON_OPTIONS.languageOptions.parserOptions,
      projectService: {
        allowDefaultProject: allowedDefaultProjectPaths,
      },
      tsconfigRootDir: fixtureRoot,
    },
  },
});

export const typeAwareRuleTester = createTypeAwareRuleTester(TYPE_AWARE_FIXTURE_ROOT);

export interface RuleCaseOptions {
  filename?: string;
  options?: unknown[];
}

export interface InvalidRuleCaseOptions extends RuleCaseOptions {
  output?: string;
}

export interface RuleCaseBuilders {
  buildValidCase: (name: string, code: string, caseOptions?: RuleCaseOptions) => RuleTester.ValidTestCase;
  buildInvalidCase: (
    name: string,
    code: string,
    messageIds: string[],
    caseOptions?: InvalidRuleCaseOptions,
  ) => RuleTester.InvalidTestCase;
}

export const createRuleCaseBuilders = (defaultCaseOptions: RuleCaseOptions = {}): RuleCaseBuilders => ({
  buildValidCase: (name, code, caseOptions = {}) => ({
    name,
    code,
    ...defaultCaseOptions,
    ...caseOptions,
  }),
  buildInvalidCase: (name, code, messageIds, caseOptions = {}) => ({
    name,
    code,
    ...defaultCaseOptions,
    ...caseOptions,
    errors: messageIds.map((messageId) => ({ messageId })),
  }),
});

export interface RuleTests {
  valid: (string | RuleTester.ValidTestCase)[];
  invalid: RuleTester.InvalidTestCase[];
}

const getRuleName = (rule: RuleDefinition): string => {
  const name = objectEntries(RULE_DEFINITIONS).find(([, definition]) => definition === rule)?.[0];

  if (name === undefined) {
    throw new Error('Failed resolving the rule name. Register the rule in "RULE_DEFINITIONS" first.');
  }

  return name;
};

export const runRuleTests = (ruleTester: RuleTester, rule: RuleDefinition, tests: RuleTests): void => {
  ruleTester.run(getRuleName(rule), rule, tests);
};

export const runJsRuleTests = (rule: RuleDefinition, tests: RuleTests): void => {
  runRuleTests(jsRuleTester, rule, tests);
};

export const runTsRuleTests = (rule: RuleDefinition, tests: RuleTests): void => {
  runRuleTests(tsRuleTester, rule, tests);
};

export const runTypeAwareRuleTests = (rule: RuleDefinition, tests: RuleTests): void => {
  runRuleTests(typeAwareRuleTester, rule, tests);
};
