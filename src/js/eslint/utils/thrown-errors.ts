/**
 * @internal @brnshkr/config/eslint
 */

import { extractBlockComment } from '#eslint/utils/jsdoc.ts';
import { getDeclaredTypeNames, UNKNOWN_TYPE_NAME } from '#eslint/utils/thrown-error-types.ts';
import { collectThrownErrors, getDocumentationHost, isInlineCallback } from '#eslint/utils/thrown-error-walker.ts';

import type { ErrorKind } from '#eslint/utils/thrown-error-types.ts';
import type { FunctionNode, RuleContext } from '#eslint/utils/thrown-error-walker.ts';

interface ErrorTagRuleOptions {
  uncheckedErrors?: string[];
}

export const ERROR_TAG_RULE_SCHEMA = [
  {
    type: 'object',
    additionalProperties: false,
    properties: {
      uncheckedErrors: {
        type: 'array',
        items: {
          type: 'string',
        },
      },
    },
  },
];

export const reportMissingErrorTypes = (
  context: RuleContext,
  node: FunctionNode,
  kind: ErrorKind,
  messageId: string,
): void => {
  if (isInlineCallback(node)) {
    return;
  }

  const { uncheckedErrors = [] } = <ErrorTagRuleOptions>(context.options[0] ?? {});
  const host = getDocumentationHost(node);
  const comment = extractBlockComment(context.sourceCode.getCommentsBefore(host), host);
  const declaredTypeNames = getDeclaredTypeNames(comment, kind);

  const missingTypeNames = new Set(collectThrownErrors(context, node)
    .filter((thrownError) => thrownError.kind === kind)
    .filter(({ lineage }) => lineage.every((typeName) => !uncheckedErrors.includes(typeName)))
    .filter(({ lineage }) => (lineage.length === 0
      ? declaredTypeNames.length === 0
      : declaredTypeNames.every((typeName) => typeName !== UNKNOWN_TYPE_NAME && !lineage.includes(typeName))))
    .map(({ name }) => name));

  for (const missingTypeName of missingTypeNames) {
    context.report({
      loc: host.loc,
      messageId,
      data: {
        type: missingTypeName,
      },
    });
  }
};
