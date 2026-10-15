import { test } from 'vitest';

import { MESSAGE_ID_MISSING_REJECTS, requireRejectsRule } from '#eslint/configs/builtin/require-rejects.ts';

import {
  createRuleCaseBuilders,
  runJsRuleTests,
  runTsRuleTests,
  runTypeAwareRuleTests,
  TYPE_AWARE_FIXTURE_FILE,
  TYPE_AWARE_JS_FIXTURE_FILE,
} from '#tests/utils/rule-tester.ts';

const { buildInvalidCase, buildValidCase } = createRuleCaseBuilders();

const {
  buildInvalidCase: buildInvalidTypeAwareCase,
  buildValidCase: buildValidTypeAwareCase,
} = createRuleCaseBuilders({ filename: TYPE_AWARE_FIXTURE_FILE });

const {
  buildInvalidCase: buildInvalidJavaScriptCase,
  buildValidCase: buildValidJavaScriptCase,
} = createRuleCaseBuilders({ filename: TYPE_AWARE_JS_FIXTURE_FILE });

const FAILING_READ = '/**\n * @throws {Error} when the user is missing\n */\nconst readUser = (): string => {\n  throw new Error(\'acme\');\n};\n\n';
const REJECTION_DOCBLOCK = '/**\n * @rejects {Error} when the user is missing\n */\n';
const PATH_IMPORT = 'import path from \'node:path\';\n\n';
const REJECTING_READ = '/**\n * @rejects {Error} when the user is missing\n */\nconst loadUser = async (): Promise<string> => {\n  throw new Error(\'acme\');\n};\n\n';

test('requireRejectsRule scenarios with type information', () => {
  runTypeAwareRuleTests(requireRejectsRule, {
    valid: [
      buildValidTypeAwareCase(
        'async throw declared as a rejection',
        REJECTING_READ.replace('const loadUser', 'export const loadUser'),
      ),
      buildValidTypeAwareCase(
        'awaited rejection declared',
        `${REJECTING_READ}${REJECTION_DOCBLOCK}export const greet = async (): Promise<string> => await loadUser();\n`,
      ),
      buildValidTypeAwareCase(
        'promise neither awaited nor returned',
        `${REJECTING_READ}export const greet = (): void => {\n  void loadUser();\n};\n`,
      ),
      buildValidTypeAwareCase(
        'awaited rejection caught',
        `${REJECTING_READ}export const greet = async (): Promise<string> => {\n  try {\n    return await loadUser();\n`
        + '  } catch {\n    return \'\';\n  }\n};\n',
      ),
      buildValidTypeAwareCase(
        'returned rejection declared',
        '/**\n * @rejects {TypeError} when asked\n */\nexport const greet = (): Promise<never> => Promise.reject(new TypeError(\'acme\'));\n',
      ),
      buildValidTypeAwareCase(
        'executor rejection declared',
        '/**\n * @rejects {Error} when asked\n */\nexport const greet = (): Promise<string> => new Promise((resolve, reject) => {\n  reject(new Error(\'acme\'));\n});\n',
      ),
      buildValidTypeAwareCase(
        'synchronous throw is no rejection',
        '/**\n * @throws {Error} when asked\n */\nexport const greet = (): never => {\n  throw new Error(\'acme\');\n};\n',
      ),
    ],
    invalid: [
      buildInvalidTypeAwareCase(
        'async throw declared as a throw',
        '/**\n * @throws {Error} when asked\n */\nexport const greet = async (): Promise<never> => {\n  throw new Error(\'acme\');\n};\n',
        [MESSAGE_ID_MISSING_REJECTS],
      ),
      buildInvalidTypeAwareCase(
        'awaited rejection undeclared',
        `${REJECTING_READ}export const greet = async (): Promise<string> => await loadUser();\n`,
        [MESSAGE_ID_MISSING_REJECTS],
      ),
      buildInvalidTypeAwareCase(
        'returned rejection undeclared',
        `${REJECTING_READ}export const greet = (): Promise<string> => loadUser();\n`,
        [MESSAGE_ID_MISSING_REJECTS],
      ),
      buildInvalidTypeAwareCase(
        'synchronous callee throw inside an async function',
        `${FAILING_READ}export const greet = async (): Promise<string> => readUser();\n`,
        [MESSAGE_ID_MISSING_REJECTS],
      ),
      buildInvalidTypeAwareCase(
        'promise reject undeclared',
        'export const greet = (): Promise<never> => Promise.reject(new TypeError(\'acme\'));\n',
        [MESSAGE_ID_MISSING_REJECTS],
      ),
      buildInvalidTypeAwareCase(
        'executor rejection undeclared',
        'export const greet = (): Promise<string> => new Promise((resolve, reject) => {\n  reject(new Error(\'acme\'));\n});\n',
        [MESSAGE_ID_MISSING_REJECTS],
      ),
    ],
  });
});

test('requireRejectsRule scenarios with JSDoc type information', () => {
  runTypeAwareRuleTests(requireRejectsRule, {
    valid: [
      buildValidJavaScriptCase(
        'native argument error unreachable for a documented argument',
        `${PATH_IMPORT}/**\n * @param {string} file - the file to resolve\n */\n`
        + 'export const resolve = async (file) => path.resolve(file);\n',
      ),
    ],
    invalid: [
      buildInvalidJavaScriptCase(
        'native argument error undeclared for an untyped argument',
        `${PATH_IMPORT}export const resolve = async (file) => path.resolve(file);\n`,
        [MESSAGE_ID_MISSING_REJECTS],
      ),
      buildInvalidJavaScriptCase(
        'awaited rejection undeclared',
        '/**\n * @rejects {Error} when the user is missing\n */\nconst loadUser = async () => {\n  throw new Error(\'acme\');\n};\n\n'
        + 'export const greet = async () => await loadUser();\n',
        [MESSAGE_ID_MISSING_REJECTS],
      ),
    ],
  });
});

test('requireRejectsRule scenarios without type information', () => {
  runTsRuleTests(requireRejectsRule, {
    valid: [
      buildValidCase(
        'async throw declared',
        `${REJECTION_DOCBLOCK}export const greet = async (): Promise<never> => {\n  throw new Error('acme');\n};\n`,
      ),
    ],
    invalid: [
      buildInvalidCase(
        'async throw undeclared',
        'export const greet = async (): Promise<never> => {\n  throw new Error(\'acme\');\n};\n',
        [MESSAGE_ID_MISSING_REJECTS],
      ),
    ],
  });
});

test('requireRejectsRule works with default ESLint parser', () => {
  runJsRuleTests(requireRejectsRule, {
    valid: [
      buildValidCase(
        'async throw declared',
        `${REJECTION_DOCBLOCK}export const greet = async () => {\n  throw new Error('acme');\n};\n`,
      ),
    ],
    invalid: [
      buildInvalidCase(
        'async throw undeclared',
        'export const greet = async () => {\n  throw new Error(\'acme\');\n};\n',
        [MESSAGE_ID_MISSING_REJECTS],
      ),
    ],
  });
});
