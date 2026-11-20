/**
 * @internal @brnshkr/config/markdownlint
 */

import { packageOrganization } from '#shared/utils/package-json.ts';

import type { MicromarkToken, RuleOnError } from 'markdownlint';
import type { BuiltinRule } from '#markdownlint/types/rules.ts';

const DESTINATION_TOKEN_TYPES = new Set([
  'autolinkProtocol',
  'definitionDestinationString',
  'literalAutolinkHttp',
  'literalAutolinkWww',
  'resourceDestinationString',
]);

const walkTokens = (tokens: MicromarkToken[], onError: RuleOnError): void => {
  for (const token of tokens) {
    if (!DESTINATION_TOKEN_TYPES.has(token.type)) {
      walkTokens(token.children, onError);
    } else if (token.text !== '/' && token.text.endsWith('/')) {
      onError({
        lineNumber: token.startLine,
        detail: token.text,
        fixInfo: {
          editColumn: token.endColumn - 1,
          deleteCount: 1,
        },
      });
    }
  }
};

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/markdownlint/rules/no-trailing-slash-in-links.md
 */
export const noTrailingSlashInLinksRule: BuiltinRule = {
  names: [`${packageOrganization}/no-trailing-slash-in-links`],
  description: 'A link names its target without a trailing slash.',
  information: new URL('https://github.com/brnshkr/config/blob/master/docs/js/markdownlint/rules/no-trailing-slash-in-links.md'),
  tags: ['links'],
  parser: 'micromark',
  function: (parameters, onError): void => {
    walkTokens(parameters.parsers.micromark.tokens, onError);
  },
};
