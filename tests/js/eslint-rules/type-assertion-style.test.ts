import { test } from 'vitest';

import {
  MESSAGE_ID_EXPECTED_PARENTHESES,
  MESSAGE_ID_EXPECTED_SPACE,
  MESSAGE_ID_UNEXPECTED_PARENTHESES,
  MESSAGE_ID_UNEXPECTED_SPACE,
  typeAssertionStyleRule,
} from '../../../src/js/eslint/configs/builtin/type-assertion-style';

import { createRuleCaseBuilders, runTsRuleTests } from '../utils/rule-tester';

const { buildInvalidCase, buildValidCase } = createRuleCaseBuilders();

const NEVER = [
  {
    parentheses: 'never',
  },
];

const NEVER_SHORTHAND = ['never'];

const SPACED = [
  {
    spacing: 'always',
  },
];

test('typeAssertionStyleRule scenarios', () => {
  runTsRuleTests(typeAssertionStyleRule, {
    valid: [
      buildValidCase(
        'parenthesized awaited operand',
        'const config = <Config>(await load());\n',
      ),
      buildValidCase(
        'parenthesized typeof operand',
        'const kind = <Kind>(typeof value);\n',
      ),
      buildValidCase(
        'parenthesized new operand',
        'const service = <Service>(new Service());\n',
      ),
      buildValidCase(
        'parenthesized operand behind a double assertion',
        'const config = <Config><unknown>(await load());\n',
      ),
      buildValidCase(
        'already redundantly parenthesized operand',
        'const config = <Config>((await load()));\n',
      ),
      buildValidCase(
        'call operand needs no parentheses',
        'const config = <Config>load();\n',
      ),
      buildValidCase(
        'punctuation operand needs no parentheses',
        'const flag = <Flag>!value;\n',
      ),
      buildValidCase(
        'update operand needs no parentheses',
        'const count = <Count>counter++;\n',
      ),
      buildValidCase(
        'literal operand needs no parentheses',
        'const values = <const>[1];\n',
      ),
      buildValidCase(
        'new.target reads as a property and needs no parentheses',
        'function make() {\n  return <Target>new.target;\n}\n',
      ),
      buildValidCase(
        'dynamic import needs no parentheses',
        'const loaded = <Loaded>import(\'./loader\');\n',
      ),
      buildValidCase(
        'await without an assertion',
        'const config = await load();\n',
      ),
      buildValidCase(
        'await nested deeper than the assertion operand',
        'const config = <Config>(await load()).value;\n',
      ),
      buildValidCase(
        'parenthesized awaited operand of an as assertion',
        'const config = (await load()) as Config;\n',
      ),
      buildValidCase(
        'call operand of an as assertion',
        'const config = load() as Config;\n',
      ),
      buildValidCase(
        'bare awaited operand under the never option',
        'const config = <Config>await load();\n',
        { options: NEVER },
      ),
      buildValidCase(
        'bare awaited operand under the never shorthand',
        'const config = <Config>await load();\n',
        { options: NEVER_SHORTHAND },
      ),
      buildValidCase(
        'spaced assertion under the always spacing option',
        'const config = <Config> (await load());\n',
        { options: SPACED },
      ),
    ],
    invalid: [
      buildInvalidCase(
        'awaited operand separated by a space',
        'const config = <Config> await load();\n',
        [MESSAGE_ID_EXPECTED_PARENTHESES],
        { output: 'const config = <Config>(await load());\n' },
      ),
      buildInvalidCase(
        'awaited operand written without a space',
        'const config = <Config>await load();\n',
        [MESSAGE_ID_EXPECTED_PARENTHESES],
        { output: 'const config = <Config>(await load());\n' },
      ),
      buildInvalidCase(
        'typeof operand written without parentheses',
        'const kind = <Kind>typeof value;\n',
        [MESSAGE_ID_EXPECTED_PARENTHESES],
        { output: 'const kind = <Kind>(typeof value);\n' },
      ),
      buildInvalidCase(
        'new operand written without parentheses',
        'const service = <Service>new Service();\n',
        [MESSAGE_ID_EXPECTED_PARENTHESES],
        { output: 'const service = <Service>(new Service());\n' },
      ),
      buildInvalidCase(
        'this operand written without parentheses',
        'const value = <Value>this.value;\n',
        [MESSAGE_ID_EXPECTED_PARENTHESES],
        { output: 'const value = <Value>(this.value);\n' },
      ),
      buildInvalidCase(
        'function operand written without parentheses',
        'const handler = <Handler>function () {};\n',
        [MESSAGE_ID_EXPECTED_PARENTHESES],
        { output: 'const handler = <Handler>(function () {});\n' },
      ),
      buildInvalidCase(
        'class operand written without parentheses',
        'const Base = <Base>class {};\n',
        [MESSAGE_ID_EXPECTED_PARENTHESES],
        { output: 'const Base = <Base>(class {});\n' },
      ),
      buildInvalidCase(
        'awaited operand behind a double assertion',
        'const config = <Config><unknown> await load();\n',
        [MESSAGE_ID_EXPECTED_PARENTHESES],
        { output: 'const config = <Config><unknown>(await load());\n' },
      ),
      buildInvalidCase(
        'awaited operand inside a call argument',
        'apply(<Config> await load());\n',
        [MESSAGE_ID_EXPECTED_PARENTHESES],
        { output: 'apply(<Config>(await load()));\n' },
      ),
      buildInvalidCase(
        'bare awaited operand of an as assertion',
        'const config = await load() as Config;\n',
        [MESSAGE_ID_EXPECTED_PARENTHESES],
        { output: 'const config = (await load()) as Config;\n' },
      ),
      buildInvalidCase(
        'parenthesized awaited operand under the never option',
        'const config = <Config>(await load());\n',
        [MESSAGE_ID_UNEXPECTED_PARENTHESES],
        {
          options: NEVER,
          output: 'const config = <Config>await load();\n',
        },
      ),
      buildInvalidCase(
        'redundantly parenthesized operand under the never option',
        'const config = <Config>((await load()));\n',
        [MESSAGE_ID_UNEXPECTED_PARENTHESES],
        {
          options: NEVER,
          output: 'const config = <Config>await load();\n',
        },
      ),
      buildInvalidCase(
        'parenthesized as operand under the never option',
        'const config = (await load()) as Config;\n',
        [MESSAGE_ID_UNEXPECTED_PARENTHESES],
        {
          options: NEVER,
          output: 'const config = await load() as Config;\n',
        },
      ),
      buildInvalidCase(
        'comment before the operand is reported but not fixed',
        'const config = <Config>/* keep */ await load();\n',
        [MESSAGE_ID_EXPECTED_PARENTHESES],
      ),
      buildInvalidCase(
        'comment inside the parentheses is reported but not fixed',
        'const config = <Config>(/* keep */ await load());\n',
        [MESSAGE_ID_UNEXPECTED_PARENTHESES],
        { options: NEVER },
      ),
      buildInvalidCase(
        'spaced assertion around an operand that needs no parentheses',
        'const config = <Config> load();\n',
        [MESSAGE_ID_UNEXPECTED_SPACE],
        { output: 'const config = <Config>load();\n' },
      ),
      buildInvalidCase(
        'missing parentheses under the always spacing option',
        'const config = <Config>await load();\n',
        [MESSAGE_ID_EXPECTED_PARENTHESES],
        {
          options: SPACED,
          output: 'const config = <Config> (await load());\n',
        },
      ),
      buildInvalidCase(
        'unspaced assertion under the always spacing option',
        'const config = <Config>(await load());\n',
        [MESSAGE_ID_EXPECTED_SPACE],
        {
          options: SPACED,
          output: 'const config = <Config> (await load());\n',
        },
      ),
    ],
  });
});
