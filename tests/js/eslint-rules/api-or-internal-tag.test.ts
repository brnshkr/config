import path from 'node:path';

import { beforeEach, expect, test } from 'vitest';

import {
  apiOrInternalTagRule,
  MESSAGE_ID_MISSING_TAG,
  MESSAGE_ID_UNEXPECTED_FILE_TAG_CONFLICT,
  MESSAGE_ID_UNEXPECTED_TAG_CONFLICT,
} from '../../../src/js/eslint/configs/builtin/api-or-internal-tag';

import { TAG_API, TAG_INTERNAL } from '../../../src/js/eslint/utils/jsdoc';
import { clearPublicApiResolutionCache } from '../../../src/js/eslint/utils/public-api';
import { extractPhpStringConstants, readPhpRuleSource } from '../utils/php-rule';
import { createRuleCaseBuilders, runJsRuleTests, runTsRuleTests } from '../utils/rule-tester';

const FIXTURE_ROOT = path.resolve(import.meta.dirname, '../fixtures/eslint-rules');
const FIXTURE_INDEX = path.join(FIXTURE_ROOT, 'src/index.ts');
const FIXTURE_UNLISTED = path.join(FIXTURE_ROOT, 'src/unlisted.ts');

const RULE_OPTIONS = <const>{
  packageJsonPath: path.join(FIXTURE_ROOT, 'package.json'),
  distRoot: './dist',
  srcRoot: './src',
};

const { buildInvalidCase, buildValidCase } = createRuleCaseBuilders({
  filename: FIXTURE_INDEX,
  options: [RULE_OPTIONS],
});

beforeEach(() => {
  clearPublicApiResolutionCache();
});

// NOTICE: the trait also declares the named-argument tags, which back rules that exist only in PHP
// because named arguments have no JavaScript equivalent. They are excluded by name rather than by
// weakening the assertion, so a tag added on the PHP side still fails here until someone decides.
const PHP_ONLY_TAGS = new Set<string>(['named-arguments', 'no-named-arguments']);

test('apiOrInternalTagRule stays in sync with the PHP rule', () => {
  const source = readPhpRuleSource('Trait/RuleTrait.php');
  const tags = extractPhpStringConstants(source, 'TAG_').filter((tag) => !PHP_ONLY_TAGS.has(tag));

  expect(tags).toStrictEqual([TAG_API, TAG_INTERNAL]);
});

test('apiOrInternalTagRule scenarios', () => {
  runTsRuleTests(apiOrInternalTagRule, {
    valid: [
      buildValidCase(
        '@api tag present',
        '/** @api */\nexport const value = 1;\n',
      ),
      buildValidCase(
        '@internal tag present',
        '/** @internal */\nexport const value = 1;\n',
      ),
      buildValidCase(
        'file-level @api covers symbol',
        '/**\n * @api\n */\n\nexport const value = 1;\n',
      ),
      buildValidCase(
        'file-level @internal covers symbol',
        '/**\n * @internal\n */\n\nexport const value = 1;\n',
      ),
      buildValidCase(
        'file-level tag covers every export below it',
        '/**\n * @internal\n */\n\nexport const first = 1;\nexport const second = 2;\n',
      ),
      buildValidCase(
        'file-level tag above an import covers the exports',
        '/**\n * @internal\n */\nimport path from \'node:path\';\n\nexport const value = path;\n',
      ),
      buildValidCase(
        'non-public-API file ignored',
        'export const value = 1;\n',
        {
          filename: FIXTURE_UNLISTED,
        },
      ),
      buildValidCase(
        'symbol @api overrides a file-level @internal',
        '/**\n * @internal\n */\n\n/**\n * @api\n */\nexport const value = 1;\n',
      ),
    ],
    invalid: [
      buildInvalidCase(
        'untagged export errors',
        'export const value = 1;\n',
        [MESSAGE_ID_MISSING_TAG],
      ),
      buildInvalidCase(
        'untagged class errors',
        'export class Box {}\n',
        [MESSAGE_ID_MISSING_TAG],
      ),
      buildInvalidCase(
        'untagged interface errors',
        'export interface Shape {}\n',
        [MESSAGE_ID_MISSING_TAG],
      ),
      buildInvalidCase(
        'untagged type alias errors',
        'export type Id = string;\n',
        [MESSAGE_ID_MISSING_TAG],
      ),
      buildInvalidCase(
        'untagged enum errors',
        'export enum Color { Red }\n',
        [MESSAGE_ID_MISSING_TAG],
      ),
      buildInvalidCase(
        'untagged function errors',
        'export function go(): void {}\n',
        [MESSAGE_ID_MISSING_TAG],
      ),
      buildInvalidCase(
        'untagged default arrow errors',
        'export default (): void => {};\n',
        [MESSAGE_ID_MISSING_TAG],
      ),
      buildInvalidCase(
        'untagged default constant expression errors',
        'export default { value: 1 };\n',
        [MESSAGE_ID_MISSING_TAG],
      ),
      buildInvalidCase(
        'untagged default identifier errors',
        'const value = 1;\nexport default value;\n',
        [MESSAGE_ID_MISSING_TAG],
      ),
      buildInvalidCase(
        'symbol carrying both tags errors',
        '/**\n * @api\n * @internal\n */\nexport const value = 1;\n',
        [MESSAGE_ID_UNEXPECTED_TAG_CONFLICT],
      ),
      buildInvalidCase(
        'symbol carrying both tags errors outside a public-API file',
        '/**\n * @api\n * @internal\n */\nexport const value = 1;\n',
        [MESSAGE_ID_UNEXPECTED_TAG_CONFLICT],
        { filename: FIXTURE_UNLISTED },
      ),
      buildInvalidCase(
        'an adjacent docblock documents the symbol, not the file',
        '/**\n * @internal\n */\nexport const first = 1;\nexport const second = 2;\n',
        [MESSAGE_ID_MISSING_TAG],
      ),
      buildInvalidCase(
        'file-level block carrying both tags errors',
        '/**\n * @api\n * @internal\n */\n\n/**\n * @api\n */\nexport const value = 1;\n',
        [MESSAGE_ID_UNEXPECTED_FILE_TAG_CONFLICT],
      ),
    ],
  });
});

test('apiOrInternalTagRule works with default ESLint parser', () => {
  runJsRuleTests(apiOrInternalTagRule, {
    valid: [
      buildValidCase(
        '@api tag on JS const',
        '/** @api */\nexport const value = 1;\n',
      ),
      buildValidCase(
        'file-level @internal covers JS class',
        '/**\n * @internal\n */\n\nexport class Box {}\n',
      ),
    ],
    invalid: [
      buildInvalidCase(
        'untagged JS function errors',
        'export function go() {}\n',
        [MESSAGE_ID_MISSING_TAG],
      ),
      buildInvalidCase(
        'untagged JS class errors',
        'export class Box {}\n',
        [MESSAGE_ID_MISSING_TAG],
      ),
    ],
  });
});
