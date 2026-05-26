import path from 'node:path';

import { beforeEach, expect, test } from 'vitest';

import {
  apiOrInternalTagRule,
  MESSAGE_ID_MISSING_TAG,
} from '../../../src/js/eslint/configs/builtin/api-or-internal-tag';

import { clearPublicApiResolutionCache } from '../../../src/js/eslint/utils/public-api';
import { jsRuleTester, tsRuleTester } from '../utils/rule-tester';

const FIXTURE_ROOT = path.resolve(import.meta.dirname, '../fixtures/eslint-rules');
const FIXTURE_INDEX = path.join(FIXTURE_ROOT, 'src/index.ts');
const FIXTURE_UNLISTED = path.join(FIXTURE_ROOT, 'src/unlisted.ts');

const RULE_OPTIONS = <const>{
  packageJsonPath: path.join(FIXTURE_ROOT, 'package.json'),
  distRoot: './dist',
  srcRoot: './src',
};

beforeEach(() => {
  clearPublicApiResolutionCache();
});

test('apiOrInternalTagRule scenarios', () => {
  expect(() => {
    tsRuleTester.run('api-or-internal-tag', apiOrInternalTagRule, {
      valid: [
        {
          name: '@api tag present',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: '/** @api */\nexport const value = 1;\n',
        },
        {
          name: '@internal tag present',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: '/** @internal */\nexport const value = 1;\n',
        },
        {
          name: 'file-level @api covers symbol',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: '/** @file Foo. @api */\nexport const value = 1;\n',
        },
        {
          name: 'file-level @internal covers symbol',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: '/** @file Foo. @internal */\nexport const value = 1;\n',
        },
        {
          name: 'non-public-API file ignored',
          filename: FIXTURE_UNLISTED,
          options: [RULE_OPTIONS],
          code: 'export const value = 1;\n',
        },
      ],
      invalid: [
        {
          name: 'untagged export errors',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: 'export const value = 1;\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_TAG }],
        },
        {
          name: 'untagged class errors',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: 'export class Box {}\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_TAG }],
        },
        {
          name: 'untagged interface errors',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: 'export interface Shape {}\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_TAG }],
        },
        {
          name: 'untagged type alias errors',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: 'export type Id = string;\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_TAG }],
        },
        {
          name: 'untagged enum errors',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: 'export enum Color { Red }\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_TAG }],
        },
        {
          name: 'untagged function errors',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: 'export function go(): void {}\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_TAG }],
        },
        {
          name: 'untagged default arrow errors',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: 'export default (): void => {};\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_TAG }],
        },
        {
          name: 'untagged default constant expression errors',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: 'export default { value: 1 };\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_TAG }],
        },
        {
          name: 'untagged default identifier errors',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: 'const value = 1;\nexport default value;\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_TAG }],
        },
      ],
    });
  }).not.toThrow();
});

test('apiOrInternalTagRule works with default ESLint parser', () => {
  expect(() => {
    jsRuleTester.run('api-or-internal-tag', apiOrInternalTagRule, {
      valid: [
        {
          name: '@api tag on JS const',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: '/** @api */\nexport const value = 1;\n',
        },
        {
          name: 'file-level @internal covers JS class',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: '/** @file Foo. @internal */\nexport class Box {}\n',
        },
      ],
      invalid: [
        {
          name: 'untagged JS function errors',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: 'export function go() {}\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_TAG }],
        },
        {
          name: 'untagged JS class errors',
          filename: FIXTURE_INDEX,
          options: [RULE_OPTIONS],
          code: 'export class Box {}\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_TAG }],
        },
      ],
    });
  }).not.toThrow();
});
