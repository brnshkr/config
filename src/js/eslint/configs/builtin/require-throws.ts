/**
 * @internal @brnshkr/config/eslint
 */

import { ERROR_KIND_THROWS } from '#eslint/utils/thrown-error-types.ts';
import { ERROR_TAG_RULE_SCHEMA, reportMissingErrorTypes } from '#eslint/utils/thrown-errors.ts';

import type { TSESLint } from '@typescript-eslint/utils';
import type { RuleDefinition } from '#eslint/configs/builtin/index.ts';
import type { FunctionNode } from '#eslint/utils/thrown-error-walker.ts';

export const MESSAGE_ID_MISSING_THROWS = 'missingThrows';

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/require-throws.md
 */
export const requireThrowsRule = <const>{
  meta: {
    type: 'suggestion',
    docs: {
      description: 'Require `@throws` for every error a function throws, its callees\' included.',
      url: 'https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/require-throws.md',
    },
    schema: ERROR_TAG_RULE_SCHEMA,
    messages: {
      [MESSAGE_ID_MISSING_THROWS]: 'Missing `@throws {{{ type }}}` for what this function throws.',
    },
  },
  create: (context) => ({
    'ArrowFunctionExpression, FunctionDeclaration, FunctionExpression': (node: FunctionNode): void => {
      reportMissingErrorTypes(
        <TSESLint.RuleContext<string, unknown[]>><unknown>context,
        node,
        ERROR_KIND_THROWS,
        MESSAGE_ID_MISSING_THROWS,
      );
    },
  }),
} satisfies RuleDefinition;
