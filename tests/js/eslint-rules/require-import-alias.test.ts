import path from 'node:path';

import { expect, test } from 'vitest';

import {
  MESSAGE_ID_MISSING_ALIAS,
  MESSAGE_ID_PREFER_ALIAS,
  requireImportAliasRule,
} from '../../../src/js/eslint/configs/builtin/require-import-alias';

import { jsRuleTester } from '../utils/rule-tester';

const FIXTURE_ROOT = path.resolve(import.meta.dirname, '../fixtures/eslint-rules');
const FIXTURE_CONSUMER = path.join(FIXTURE_ROOT, 'src/consumer.ts');
const FIXTURE_ALIAS_BASE = path.posix.join(path.posix.resolve(FIXTURE_ROOT), 'src/lib');

const RULE_OPTIONS = <const>{
  aliases: {
    '$test/*': [`${FIXTURE_ALIAS_BASE}/*`],
  },
};

const OVERLAPPING_RULE_OPTIONS = <const>{
  aliases: {
    '$root/*': [`${path.posix.dirname(FIXTURE_ALIAS_BASE)}/*`],
    '$lib/*': [`${FIXTURE_ALIAS_BASE}/*`],
  },
};

test('requireImportAliasRule scenarios', () => {
  expect(() => {
    jsRuleTester.run('require-import-alias', requireImportAliasRule, {
      valid: [
        {
          name: 'no aliases configured leaves rule inert',
          filename: FIXTURE_CONSUMER,
          options: [
            {
              aliases: {},
            },
          ],
          code: 'import { x } from \'./lib/target\';\n',
        },
        {
          name: 'already-aliased import is accepted',
          filename: FIXTURE_CONSUMER,
          options: [RULE_OPTIONS],
          code: 'import { x } from \'$test/target\';\n',
        },
        {
          name: 'bare module specifier is ignored',
          filename: FIXTURE_CONSUMER,
          options: [RULE_OPTIONS],
          code: 'import { x } from \'lodash\';\n',
        },
        {
          name: 'alias with the fewest path segments is accepted',
          filename: FIXTURE_CONSUMER,
          options: [OVERLAPPING_RULE_OPTIONS],
          code: 'import { x } from \'$lib/target\';\n',
        },
        {
          name: 'ignored path skips lint',
          filename: FIXTURE_CONSUMER,
          options: [
            {
              aliases: RULE_OPTIONS.aliases,
              ignoredPaths: ['**/consumer.ts'],
            },
          ],
          code: 'import { x } from \'./lib/target\';\n',
        },
      ],
      invalid: [
        {
          name: 'relative import resolvable as alias is autofixed',
          filename: FIXTURE_CONSUMER,
          options: [RULE_OPTIONS],
          code: 'import { x } from \'./lib/target\';\n',
          errors: [{ messageId: MESSAGE_ID_PREFER_ALIAS }],
          output: 'import { x } from \'$test/target\';\n',
        },
        {
          name: 'relative import prefers the alias with the fewest path segments',
          filename: FIXTURE_CONSUMER,
          options: [OVERLAPPING_RULE_OPTIONS],
          code: 'import { x } from \'./lib/target\';\n',
          errors: [{ messageId: MESSAGE_ID_PREFER_ALIAS }],
          output: 'import { x } from \'$lib/target\';\n',
        },
        {
          name: 'alias with more path segments than needed is autofixed',
          filename: FIXTURE_CONSUMER,
          options: [OVERLAPPING_RULE_OPTIONS],
          code: 'import { x } from \'$root/lib/target\';\n',
          errors: [{ messageId: MESSAGE_ID_PREFER_ALIAS }],
          output: 'import { x } from \'$lib/target\';\n',
        },
        {
          name: 'relative import outside any alias root reports missingAlias',
          filename: FIXTURE_CONSUMER,
          options: [RULE_OPTIONS],
          code: 'import { y } from \'../outside/file\';\n',
          errors: [{ messageId: MESSAGE_ID_MISSING_ALIAS }],
        },
        {
          name: 'autofix preserves double-quoted source',
          filename: FIXTURE_CONSUMER,
          options: [RULE_OPTIONS],
          code: 'import { x } from "./lib/target";\n',
          errors: [{ messageId: MESSAGE_ID_PREFER_ALIAS }],
          output: 'import { x } from "$test/target";\n',
        },
        {
          name: 'export-from is also checked',
          filename: FIXTURE_CONSUMER,
          options: [RULE_OPTIONS],
          code: 'export { x } from \'./lib/target\';\n',
          errors: [{ messageId: MESSAGE_ID_PREFER_ALIAS }],
          output: 'export { x } from \'$test/target\';\n',
        },
        {
          name: 'export-* is also checked',
          filename: FIXTURE_CONSUMER,
          options: [RULE_OPTIONS],
          code: 'export * from \'./lib/target\';\n',
          errors: [{ messageId: MESSAGE_ID_PREFER_ALIAS }],
          output: 'export * from \'$test/target\';\n',
        },
        {
          name: 'dynamic import is also checked',
          filename: FIXTURE_CONSUMER,
          options: [RULE_OPTIONS],
          code: 'const value = import(\'./lib/target\');\n',
          errors: [{ messageId: MESSAGE_ID_PREFER_ALIAS }],
          output: 'const value = import(\'$test/target\');\n',
        },
      ],
    });
  }).not.toThrow();
});
