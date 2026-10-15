/**
 * @internal @brnshkr/config/eslint
 */

import { ERROR_KIND_REJECTS } from '#eslint/utils/thrown-error-types.ts';
import { ERROR_TAG_RULE_SCHEMA, reportMissingErrorTypes } from '#eslint/utils/thrown-errors.ts';

import type { TSESLint } from '@typescript-eslint/utils';
import type { RuleDefinition } from '#eslint/configs/builtin/index.ts';
import type { FunctionNode } from '#eslint/utils/thrown-error-walker.ts';

export const MESSAGE_ID_MISSING_REJECTS = 'missingRejects';

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/require-rejects.md
 */
export const requireRejectsRule = <const>{
  meta: {
    type: 'suggestion',
    docs: {
      description: 'Require `@rejects` for every error a function\'s promise rejects with, its callees\' included.',
      url: 'https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/require-rejects.md',
    },
    schema: ERROR_TAG_RULE_SCHEMA,
    messages: {
      [MESSAGE_ID_MISSING_REJECTS]: 'Missing `@rejects {{{ type }}}` for what this function\'s promise rejects with.',
    },
  },
  create: (context) => ({
    'ArrowFunctionExpression, FunctionDeclaration, FunctionExpression': (node: FunctionNode): void => {
      reportMissingErrorTypes(
        <TSESLint.RuleContext<string, unknown[]>><unknown>context,
        node,
        ERROR_KIND_REJECTS,
        MESSAGE_ID_MISSING_REJECTS,
      );
    },
  }),
} satisfies RuleDefinition;
