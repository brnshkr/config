/**
 * @internal @brnshkr/config/markdownlint
 */

import { packageOrganization } from '../../../shared/utils/package-json';
import { createPattern } from '../../../shared/utils/pattern';

import type { MicromarkToken, Rule, RuleOnError } from 'markdownlint';

const SCANNED_TOKEN_TYPES = new Set([
  'codeFlowValue',
  'data',
  'htmlFlow',
  'htmlText',
]);

const WARNING_TERMS = [
  'FIXME',
  'HACK',
  'TODO',
  'XXX',
];

const createWarningTermPattern = (): RegExp => createPattern('gv')`\b(?:${WARNING_TERMS})\b`;

const reportWarningTerms = (token: MicromarkToken, onError: RuleOnError): void => {
  for (const match of token.text.matchAll(createWarningTermPattern())) {
    const precedingLines = token.text.slice(0, match.index).split('\n');

    onError({
      lineNumber: token.startLine + (precedingLines.length - 1),
      detail: match[0],
    });
  }
};

const walkTokens = (tokens: MicromarkToken[], onError: RuleOnError): void => {
  for (const token of tokens) {
    if (SCANNED_TOKEN_TYPES.has(token.type)) {
      reportWarningTerms(token, onError);
    } else {
      walkTokens(token.children, onError);
    }
  }
};

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/markdownlint/rules/no-warning-comments.md
 */
export const noWarningCommentsRule = {
  names: [`${packageOrganization}/no-warning-comments`],
  description: 'Open work belongs in a backlog, not in a comment.',
  information: new URL('https://github.com/brnshkr/config/blob/master/docs/js/markdownlint/rules/no-warning-comments.md'),
  tags: ['comments'],
  parser: 'micromark',
  function: (parameters, onError): void => {
    walkTokens(parameters.parsers.micromark.tokens, onError);
  },
} satisfies Rule;
