/**
 * @internal @brnshkr/config/eslint
 */

import type { TSESLint, TSESTree } from '@typescript-eslint/utils';
import type { Maybe } from '../../../shared/types/core';
import type { RuleDefinition } from '.';

export const MESSAGE_ID_EXPECTED_PARENTHESES = 'expectedParentheses';
export const MESSAGE_ID_UNEXPECTED_PARENTHESES = 'unexpectedParentheses';
export const MESSAGE_ID_EXPECTED_SPACE = 'expectedSpace';
export const MESSAGE_ID_UNEXPECTED_SPACE = 'unexpectedSpace';

const ASSERTION_SELECTOR = 'TSTypeAssertion, TSAsExpression';

const OPERAND_KEYWORDS = new Set([
  'async',
  'await',
  'class',
  'delete',
  'function',
  'new',
  'super',
  'this',
  'typeof',
  'void',
]);

const REQUIREMENTS = <const>[
  'always',
  'never',
];

type Requirement = typeof REQUIREMENTS[number];
type AssertionNode = TSESTree.TSAsExpression | TSESTree.TSTypeAssertion;
type SourceRange = [number, number];

type TypeAssertionStyleOptions = Requirement | {
  parentheses?: Requirement;
  spacing?: Requirement;
};

const REQUIREMENT_SCHEMA = <const>{
  type: 'string',
  enum: REQUIREMENTS,
};

const isKeywordOperand = (sourceCode: TSESLint.SourceCode, operand: TSESTree.Node): boolean => {
  const [firstToken, secondToken] = sourceCode.getTokens(operand);

  if (firstToken === undefined || !OPERAND_KEYWORDS.has(firstToken.value)) {
    return false;
  }

  return firstToken.value !== 'new' || secondToken?.value !== '.';
};

const isParenthesizedOperand = (
  sourceCode: TSESLint.SourceCode,
  node: TSESTree.Node,
): boolean => sourceCode.getTokenBefore(node)?.value === '('
  && sourceCode.getTokenAfter(node)?.value === ')';

const resolveOperandRange = (
  sourceCode: TSESLint.SourceCode,
  assertion: AssertionNode,
  isAngleBracket: boolean,
): Maybe<SourceRange> => {
  const [start, end] = isAngleBracket
    ? [sourceCode.getTokenAfter(assertion.typeAnnotation)?.range[1], assertion.range[1]]
    : [assertion.range[0], sourceCode.getTokenBefore(assertion.typeAnnotation)?.range[0]];

  return (start === undefined || end === undefined)
    ? undefined
    : [start, end];
};

const hasCommentInAnyRange = (sourceCode: TSESLint.SourceCode, ranges: SourceRange[]): boolean => sourceCode
  .getAllComments()
  .some(({ range }) => ranges.some(([start, end]) => range[0] < end && range[1] > start));

const resolveGapRange = (
  sourceCode: TSESLint.SourceCode,
  assertion: TSESTree.TSTypeAssertion,
): Maybe<SourceRange> => {
  const closingAngle = sourceCode.getTokenAfter(assertion.typeAnnotation) ?? undefined;

  const operandStart = closingAngle === undefined
    ? undefined
    : (sourceCode.getTokenAfter(closingAngle) ?? undefined);

  const isUnparenthesizedKeyword = isKeywordOperand(sourceCode, assertion.expression)
    && !isParenthesizedOperand(sourceCode, assertion.expression);

  return (closingAngle === undefined || operandStart === undefined || isUnparenthesizedKeyword)
    ? undefined
    : [closingAngle.range[1], operandStart.range[0]];
};

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/type-assertion-style.md
 */
export const typeAssertionStyleRule = <const>{
  meta: {
    type: 'layout',
    fixable: 'code',
    docs: {
      description: 'Require a type assertion to parenthesize an operand that reads wider than it casts, and to carry no trailing space.',
      url: 'https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/type-assertion-style.md',
    },
    schema: [
      {
        oneOf: [
          REQUIREMENT_SCHEMA,
          {
            type: 'object',
            additionalProperties: false,
            properties: {
              parentheses: REQUIREMENT_SCHEMA,
              spacing: REQUIREMENT_SCHEMA,
            },
          },
        ],
      },
    ],
    messages: {
      [MESSAGE_ID_EXPECTED_PARENTHESES]: 'The operand of a type assertion must be parenthesized.',
      [MESSAGE_ID_UNEXPECTED_PARENTHESES]: 'The operand of a type assertion must not be parenthesized.',
      [MESSAGE_ID_EXPECTED_SPACE]: 'A type assertion must be followed by a space.',
      [MESSAGE_ID_UNEXPECTED_SPACE]: 'A type assertion must not be followed by a space.',
    },
  },
  create: (context) => {
    const options = <Maybe<TypeAssertionStyleOptions>>context.options[0];
    const parentheses = (typeof options === 'string' ? options : options?.parentheses) ?? 'always';
    const spacing = (typeof options === 'string' ? undefined : options?.spacing) ?? 'never';
    const sourceCode = <TSESLint.SourceCode><unknown>context.sourceCode;
    const assertionsWithParenthesesFix = new Set<TSESTree.Node>();
    const gapText = spacing === 'always' ? ' ' : '';

    return {
      [ASSERTION_SELECTOR]: (assertion: AssertionNode): void => {
        const node = assertion.expression;

        if (!isKeywordOperand(sourceCode, node)) {
          return;
        }

        const isParenthesized = isParenthesizedOperand(sourceCode, node);

        if (isParenthesized === (parentheses === 'always')) {
          return;
        }

        const isAngleBracket = assertion.typeAnnotation.range[0] < node.range[0];
        const operandRange = resolveOperandRange(sourceCode, assertion, isAngleBracket);

        if (operandRange === undefined) {
          return;
        }

        if (isAngleBracket) {
          assertionsWithParenthesesFix.add(assertion);
        }

        const operandText = sourceCode.getText(node);
        const replacementText = isParenthesized ? operandText : `(${operandText})`;

        const erasedRanges: SourceRange[] = [
          [operandRange[0], node.range[0]],
          [node.range[1], operandRange[1]],
        ];

        context.report({
          node,
          messageId: isParenthesized ? MESSAGE_ID_UNEXPECTED_PARENTHESES : MESSAGE_ID_EXPECTED_PARENTHESES,
          fix: (fixer) => (hasCommentInAnyRange(sourceCode, erasedRanges)
            ? []
            : [fixer.replaceTextRange(
              operandRange,
              isAngleBracket ? `${gapText}${replacementText}` : `${replacementText} `,
            )]),
        });
      },
      'TSTypeAssertion:exit': (node: TSESTree.TSTypeAssertion): void => {
        const gapRange = resolveGapRange(sourceCode, node);

        if (gapRange === undefined || assertionsWithParenthesesFix.has(node)) {
          return;
        }

        const isSpaced = gapRange[0] !== gapRange[1];

        if (isSpaced === (spacing === 'always')) {
          return;
        }

        context.report({
          node,
          messageId: isSpaced ? MESSAGE_ID_UNEXPECTED_SPACE : MESSAGE_ID_EXPECTED_SPACE,
          fix: (fixer) => (hasCommentInAnyRange(sourceCode, [gapRange])
            ? []
            : [fixer.replaceTextRange(gapRange, gapText)]),
        });
      },
    };
  },
} satisfies RuleDefinition;
