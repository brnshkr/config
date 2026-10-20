/**
 * @internal @brnshkr/config/markdownlint
 */

import { packageOrganization } from '#shared/utils/package-json.ts';

import type { MicromarkToken, RuleOnError } from 'markdownlint';
import type { BuiltinRule } from '#markdownlint/types/rules.ts';

const createOpenTagPattern = (): RegExp => /<\?[\w=]*/gv;
const createStrictTypesPattern = (): RegExp => /\bdeclare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/gv;

const reportMatches = (text: string, firstLineNumber: number, pattern: RegExp, onError: RuleOnError): void => {
  for (const match of text.matchAll(pattern)) {
    const precedingLines = text.slice(0, match.index).split('\n');

    onError({
      lineNumber: firstLineNumber + (precedingLines.length - 1),
      detail: match[0],
    });
  }
};

const walkPhpFences = (tokens: MicromarkToken[], onError: RuleOnError): void => {
  for (const token of tokens) {
    const isPhpFence = token.type === 'codeFenced'
      && token.children.some((fenceToken) => fenceToken.type === 'codeFencedFence'
        && fenceToken.children.some((infoToken) => infoToken.type === 'codeFencedFenceInfo'
          && infoToken.text.toLowerCase() === 'php'));

    if (isPhpFence) {
      reportMatches(
        token.text,
        token.startLine,
        createStrictTypesPattern(),
        onError,
      );
    } else {
      walkPhpFences(token.children, onError);
    }
  }
};

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/markdownlint/rules/no-php-open-tag.md
 */
export const noPhpOpenTagRule: BuiltinRule = {
  names: [`${packageOrganization}/no-php-open-tag`],
  description: 'A PHP open tag turns the rest of the page into PHP for the PHP tools.',
  information: new URL('https://github.com/brnshkr/config/blob/master/docs/js/markdownlint/rules/no-php-open-tag.md'),
  tags: ['code'],
  parser: 'micromark',
  function: (parameters, onError): void => {
    reportMatches(
      parameters.lines.join('\n'),
      1,
      createOpenTagPattern(),
      onError,
    );

    walkPhpFences(parameters.parsers.micromark.tokens, onError);
  },
};
