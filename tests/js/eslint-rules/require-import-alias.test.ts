import path from 'node:path';

import { test } from 'vitest';

import {
  MESSAGE_ID_MISSING_ALIAS,
  MESSAGE_ID_PREFER_ALIAS,
  requireImportAliasRule,
} from '../../../src/js/eslint/configs/builtin/require-import-alias';

import { createRuleCaseBuilders, runJsRuleTests } from '../utils/rule-tester';

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

const { buildInvalidCase, buildValidCase } = createRuleCaseBuilders({
  filename: FIXTURE_CONSUMER,
  options: [RULE_OPTIONS],
});

test('requireImportAliasRule scenarios', () => {
  runJsRuleTests(requireImportAliasRule, {
    valid: [
      buildValidCase(
        'no aliases configured leaves rule inert',
        'import { x } from \'./lib/target\';\n',
        {
          options: [
            {
              aliases: {},
            },
          ],
        },
      ),
      buildValidCase(
        'already-aliased import is accepted',
        'import { x } from \'$test/target\';\n',
      ),
      buildValidCase(
        'bare module specifier is ignored',
        'import { x } from \'lodash\';\n',
      ),
      buildValidCase(
        'alias with the fewest path segments is accepted',
        'import { x } from \'$lib/target\';\n',
        {
          options: [OVERLAPPING_RULE_OPTIONS],
        },
      ),
      buildValidCase(
        'ignored path skips lint',
        'import { x } from \'./lib/target\';\n',
        {
          options: [
            {
              aliases: RULE_OPTIONS.aliases,
              ignoredPaths: ['**/consumer.ts'],
            },
          ],
        },
      ),
    ],
    invalid: [
      buildInvalidCase(
        'relative import resolvable as alias is autofixed',
        'import { x } from \'./lib/target\';\n',
        [MESSAGE_ID_PREFER_ALIAS],
        {
          output: 'import { x } from \'$test/target\';\n',
        },
      ),
      buildInvalidCase(
        'relative import prefers the alias with the fewest path segments',
        'import { x } from \'./lib/target\';\n',
        [MESSAGE_ID_PREFER_ALIAS],
        {
          options: [OVERLAPPING_RULE_OPTIONS],
          output: 'import { x } from \'$lib/target\';\n',
        },
      ),
      buildInvalidCase(
        'alias with more path segments than needed is autofixed',
        'import { x } from \'$root/lib/target\';\n',
        [MESSAGE_ID_PREFER_ALIAS],
        {
          options: [OVERLAPPING_RULE_OPTIONS],
          output: 'import { x } from \'$lib/target\';\n',
        },
      ),
      buildInvalidCase(
        'relative import outside any alias root reports missingAlias',
        'import { y } from \'../outside/file\';\n',
        [MESSAGE_ID_MISSING_ALIAS],
      ),
      buildInvalidCase(
        'autofix preserves double-quoted source',
        'import { x } from "./lib/target";\n',
        [MESSAGE_ID_PREFER_ALIAS],
        {
          output: 'import { x } from "$test/target";\n',
        },
      ),
      buildInvalidCase(
        'export-from is also checked',
        'export { x } from \'./lib/target\';\n',
        [MESSAGE_ID_PREFER_ALIAS],
        {
          output: 'export { x } from \'$test/target\';\n',
        },
      ),
      buildInvalidCase(
        'export-* is also checked',
        'export * from \'./lib/target\';\n',
        [MESSAGE_ID_PREFER_ALIAS],
        {
          output: 'export * from \'$test/target\';\n',
        },
      ),
      buildInvalidCase(
        'dynamic import is also checked',
        'const value = import(\'./lib/target\');\n',
        [MESSAGE_ID_PREFER_ALIAS],
        {
          output: 'const value = import(\'$test/target\');\n',
        },
      ),
    ],
  });
});
