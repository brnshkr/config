import { test } from 'vitest';

import {
  MESSAGE_ID_MISSING_TYPE_PROPERTY,
  MESSAGE_ID_MISSING_WITH_KEYWORD,
  MESSAGE_ID_WRONG_TYPE_VALUE,
  requireImportAttributesRule,
} from '../../../src/js/eslint/configs/builtin/require-import-attributes';

import { createRuleCaseBuilders, runJsRuleTests } from '../utils/rule-tester';

const { buildInvalidCase, buildValidCase } = createRuleCaseBuilders();

test('requireImportAttributesRule scenarios', () => {
  runJsRuleTests(requireImportAttributesRule, {
    valid: [
      buildValidCase(
        'json import with matching attribute',
        'import data from \'./data.json\' with { type: \'json\' };\n',
      ),
      buildValidCase(
        'css import with matching attribute',
        'import styles from \'./styles.css\' with { type: \'css\' };\n',
      ),
      buildValidCase(
        'svg import with matching attribute',
        'import icon from \'./icon.svg\' with { type: \'svg\' };\n',
      ),
      buildValidCase(
        'unknown extension is left alone',
        'import { x } from \'./module.ts\';\n',
      ),
      buildValidCase(
        'bare specifier is left alone',
        'import lodash from \'lodash\';\n',
      ),
      buildValidCase(
        'specifier without extension is left alone',
        'import { x } from \'./module\';\n',
      ),
    ],
    invalid: [
      buildInvalidCase(
        'json without attributes',
        'import data from \'./data.json\';\n',
        [MESSAGE_ID_MISSING_WITH_KEYWORD],
      ),
      buildInvalidCase(
        'css without attributes',
        'import styles from \'./styles.css\';\n',
        [MESSAGE_ID_MISSING_WITH_KEYWORD],
      ),
      buildInvalidCase(
        'attributes object without type key',
        'import data from \'./data.json\' with { foo: \'bar\' };\n',
        [MESSAGE_ID_MISSING_TYPE_PROPERTY],
      ),
      buildInvalidCase(
        'wrong type value for json',
        'import data from \'./data.json\' with { type: \'text\' };\n',
        [MESSAGE_ID_WRONG_TYPE_VALUE],
      ),
      buildInvalidCase(
        'wrong type value for css',
        'import styles from \'./styles.css\' with { type: \'json\' };\n',
        [MESSAGE_ID_WRONG_TYPE_VALUE],
      ),
    ],
  });
});
