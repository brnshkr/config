import { expect, test } from 'vitest';

import {
  MESSAGE_ID_MISSING_TYPE_PROPERTY,
  MESSAGE_ID_MISSING_WITH_KEYWORD,
  MESSAGE_ID_WRONG_TYPE_VALUE,
  requireImportAttributesRule,
} from '../../../src/js/eslint/configs/builtin/require-import-attributes';

import { jsRuleTester } from '../utils/rule-tester';

test('requireImportAttributesRule scenarios', () => {
  expect(() => {
    jsRuleTester.run('require-import-attributes', requireImportAttributesRule, {
      valid: [
        {
          name: 'json import with matching attribute',
          code: 'import data from \'./data.json\' with { type: \'json\' };\n',
        },
        {
          name: 'css import with matching attribute',
          code: 'import styles from \'./styles.css\' with { type: \'css\' };\n',
        },
        {
          name: 'svg import with matching attribute',
          code: 'import icon from \'./icon.svg\' with { type: \'svg\' };\n',
        },
        {
          name: 'unknown extension is left alone',
          code: 'import { x } from \'./module.ts\';\n',
        },
        {
          name: 'bare specifier is left alone',
          code: 'import lodash from \'lodash\';\n',
        },
        {
          name: 'specifier without extension is left alone',
          code: 'import { x } from \'./module\';\n',
        },
      ],
      invalid: [
        {
          name: 'json without attributes',
          code: 'import data from \'./data.json\';\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_WITH_KEYWORD }],
        },
        {
          name: 'css without attributes',
          code: 'import styles from \'./styles.css\';\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_WITH_KEYWORD }],
        },
        {
          name: 'attributes object without type key',
          code: 'import data from \'./data.json\' with { foo: \'bar\' };\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_TYPE_PROPERTY }],
        },
        {
          name: 'wrong type value for json',
          code: 'import data from \'./data.json\' with { type: \'text\' };\n',
          errors: [{ messageId: MESSAGE_ID_WRONG_TYPE_VALUE }],
        },
        {
          name: 'wrong type value for css',
          code: 'import styles from \'./styles.css\' with { type: \'json\' };\n',
          errors: [{ messageId: MESSAGE_ID_WRONG_TYPE_VALUE }],
        },
      ],
    });
  }).not.toThrow();
});
