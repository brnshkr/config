import { test } from 'vitest';

import { MESSAGE_ID_MISSING_THROWS, requireThrowsRule } from '#eslint/configs/builtin/require-throws.ts';

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
const THROW_DOCBLOCK = '/**\n * @throws {Error} when the user is missing\n */\n';
const PATH_IMPORT = 'import path from \'node:path\';\n\n';

test('requireThrowsRule scenarios with type information', () => {
  runTypeAwareRuleTests(requireThrowsRule, {
    valid: [
      buildValidTypeAwareCase(
        'direct throw declared',
        '/**\n * @throws {Error} when the name is empty\n */\nexport const greet = (name: string): string => {\n  if (name === \'\') {\n    throw new Error(\'acme\');\n  }\n\n  return name;\n};\n',
      ),
      buildValidTypeAwareCase(
        'subclass covered by its declared parent',
        '/**\n * @throws {Error} when the name is empty\n */\nexport function greet(name: string): string {\n  if (name === \'\') {\n    throw new TypeError(\'acme\');\n  }\n\n  return name;\n}\n',
      ),
      buildValidTypeAwareCase(
        'own error class covered by its declared parent',
        'class UserError extends Error {}\n\n/**\n * @throws {Error} when the user is missing\n */\nexport const greet = (): never => {\n  throw new UserError(\'acme\');\n};\n',
      ),
      buildValidTypeAwareCase(
        'throw inside a try with a catch',
        'export const greet = (): string => {\n  try {\n    throw new Error(\'acme\');\n  } catch {\n    return \'\';\n  }\n};\n',
      ),
      buildValidTypeAwareCase(
        'callee declares and caller declares',
        `${FAILING_READ}${THROW_DOCBLOCK}export const greet = (): string => readUser();\n`,
      ),
      buildValidTypeAwareCase(
        'callee from the standard library declared',
        '/**\n * @throws {SyntaxError} when the text is no JSON\n */\nexport const parse = (text: string): unknown => JSON.parse(text);\n',
      ),
      buildValidTypeAwareCase(
        'unchecked error',
        'export const greet = (): never => {\n  throw new TypeError(\'acme\');\n};\n',
        { options: [{ uncheckedErrors: ['TypeError'] }] },
      ),
      buildValidTypeAwareCase(
        'subclass of an unchecked error',
        'export const greet = (): never => {\n  throw new TypeError(\'acme\');\n};\n',
        { options: [{ uncheckedErrors: ['Error'] }] },
      ),
      buildValidTypeAwareCase(
        'inline callback throw declared on the enclosing function',
        `${FAILING_READ}${THROW_DOCBLOCK}export const greet = (ids: string[]): string[] => ids.map(() => readUser());`,
      ),
      buildValidTypeAwareCase(
        'nested named function declares its own',
        'export const greet = (): void => {\n  /**\n   * @throws {Error} when called\n   */\n  const fail = (): never => {\n    throw new Error(\'acme\');\n  };\n\n  void fail;\n};\n',
      ),
      buildValidTypeAwareCase(
        'rethrown value of unknown type declared',
        '/**\n * @throws {Error} when parsing fails\n */\nexport const parse = (text: string): unknown => {\n  try {\n    return JSON.parse(text);\n  } catch (error) {\n    throw error;\n  }\n};\n',
      ),
      buildValidTypeAwareCase(
        'object property function declared',
        'export const handlers = {\n  /**\n   * @throws {Error} when called\n   */\n  fail: (): never => {\n    throw new Error(\'acme\');\n  },\n};\n',
      ),
      buildValidTypeAwareCase(
        'class method declared',
        'export class Mailer {\n  /**\n   * @throws {Error} when sending fails\n   */\n  public send(): never {\n    throw new Error(\'acme\');\n  }\n}\n',
      ),
      buildValidTypeAwareCase(
        'native argument error unreachable for a typed argument',
        `${PATH_IMPORT}export const resolve = (file: string): string => path.resolve(file);\n`,
      ),
      buildValidTypeAwareCase(
        'native argument error unreachable for an any argument',
        `${PATH_IMPORT}export const resolve = (file: any): string => path.resolve(file);\n`,
      ),
      buildValidTypeAwareCase(
        'throw inside an async function rejects instead',
        '/**\n * @rejects {Error} when asked\n */\nexport const greet = async (): Promise<never> => {\n  throw new Error(\'acme\');\n};\n',
      ),
    ],
    invalid: [
      buildInvalidTypeAwareCase(
        'direct throw undeclared',
        'export const greet = (): never => {\n  throw new Error(\'acme\');\n};\n',
        [MESSAGE_ID_MISSING_THROWS],
      ),
      buildInvalidTypeAwareCase(
        'declared type narrower than the throw',
        '/**\n * @throws {TypeError} when asked\n */\nexport const greet = (): never => {\n  throw new Error(\'acme\');\n};\n',
        [MESSAGE_ID_MISSING_THROWS],
      ),
      buildInvalidTypeAwareCase(
        'callee throw undeclared',
        `${FAILING_READ}export const greet = (): string => readUser();\n`,
        [MESSAGE_ID_MISSING_THROWS],
      ),
      buildInvalidTypeAwareCase(
        'standard library callee undeclared',
        'export const parse = (text: string): unknown => JSON.parse(text);\n',
        [MESSAGE_ID_MISSING_THROWS],
      ),
      buildInvalidTypeAwareCase(
        'standard library type error undeclared',
        'export const serialize = (value: object): string => JSON.stringify(value);\n',
        [MESSAGE_ID_MISSING_THROWS],
      ),
      buildInvalidTypeAwareCase(
        'constructor throw undeclared',
        'class Mailer {\n  /**\n   * @throws {Error} when no transport exists\n   */\n  public constructor() {\n    throw new Error(\'acme\');\n  }\n}\n\nexport const create = (): Mailer => new Mailer();\n',
        [MESSAGE_ID_MISSING_THROWS],
      ),
      buildInvalidTypeAwareCase(
        'inline callback throw undeclared on the enclosing function',
        `${FAILING_READ}export const greet = (names: string[]): string[] => names.map(() => readUser());\n`,
        [MESSAGE_ID_MISSING_THROWS],
      ),
      buildInvalidTypeAwareCase(
        'rethrown value of unknown type undeclared',
        'export const parse = (text: string): unknown => {\n  try {\n    return JSON.parse(text);\n  } catch (error) {\n    throw error;\n  }\n};\n',
        [MESSAGE_ID_MISSING_THROWS],
      ),
      buildInvalidTypeAwareCase(
        'one type thrown twice reported once',
        'export const greet = (name: string): never => {\n  if (name === \'\') {\n    throw new TypeError(\'acme\');\n  }\n\n  throw new TypeError(\'acme\');\n};\n',
        [MESSAGE_ID_MISSING_THROWS],
      ),
      buildInvalidTypeAwareCase(
        'each missing type reported once',
        'export const greet = (name: string): never => {\n  if (name === \'\') {\n    throw new TypeError(\'acme\');\n  }\n\n  throw new RangeError(\'acme\');\n};\n',
        [MESSAGE_ID_MISSING_THROWS, MESSAGE_ID_MISSING_THROWS],
      ),
    ],
  });
});

test('requireThrowsRule scenarios with JSDoc type information', () => {
  runTypeAwareRuleTests(requireThrowsRule, {
    valid: [
      buildValidJavaScriptCase(
        'native argument error unreachable for a documented argument',
        `${PATH_IMPORT}/**\n * @param {string} file - the file to resolve\n */\n`
        + 'export const resolve = (file) => path.resolve(file);\n',
      ),
      buildValidJavaScriptCase(
        'native argument error declared for an untyped argument',
        `${PATH_IMPORT}/**\n * @throws {TypeError} when the file is no string\n */\n`
        + 'export const resolve = (file) => path.resolve(file);\n',
      ),
    ],
    invalid: [
      buildInvalidJavaScriptCase(
        'native argument error undeclared for an untyped argument',
        `${PATH_IMPORT}export const resolve = (file) => path.resolve(file);\n`,
        [MESSAGE_ID_MISSING_THROWS],
      ),
      buildInvalidJavaScriptCase(
        'native argument error undeclared for an unknown argument',
        `${PATH_IMPORT}/**\n * @param {unknown} file - the file to resolve\n */\n`
        + 'export const resolve = (file) => path.resolve(file);\n',
        [MESSAGE_ID_MISSING_THROWS],
      ),
      buildInvalidJavaScriptCase(
        'callee throw undeclared',
        '/**\n * @throws {Error} when the user is missing\n */\nconst readUser = () => {\n  throw new Error(\'acme\');\n};\n\nexport const greet = () => readUser();\n',
        [MESSAGE_ID_MISSING_THROWS],
      ),
    ],
  });
});

test('requireThrowsRule scenarios without type information', () => {
  runTsRuleTests(requireThrowsRule, {
    valid: [
      buildValidCase(
        'any declared type covers a throw of unknown type',
        '/**\n * @throws {Error} when asked\n */\nexport const greet = (): never => {\n  throw new TypeError(\'acme\');\n};\n',
      ),
      buildValidCase(
        'callees are not followed',
        `${FAILING_READ}export const greet = (): string => readUser();\n`,
      ),
    ],
    invalid: [
      buildInvalidCase(
        'direct throw undeclared',
        'export const greet = (): never => {\n  throw new Error(\'acme\');\n};\n',
        [MESSAGE_ID_MISSING_THROWS],
      ),
    ],
  });
});

test('requireThrowsRule works with default ESLint parser', () => {
  runJsRuleTests(requireThrowsRule, {
    valid: [
      buildValidCase(
        'any declared type covers a throw of unknown type',
        '/**\n * @throws {Error} when asked\n */\nexport const greet = () => {\n  throw new TypeError(\'acme\');\n};\n',
      ),
    ],
    invalid: [
      buildInvalidCase(
        'direct throw undeclared',
        'export const greet = () => {\n  throw new Error(\'acme\');\n};\n',
        [MESSAGE_ID_MISSING_THROWS],
      ),
    ],
  });
});
