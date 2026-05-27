import { buildExportVisitors } from '../../utils/exports';
import { getEffectiveVisibilityTag, getFileLevelBlockComment } from '../../utils/jsdoc';
import { isPublicApiFile } from '../../utils/public-api';

import type { TSESLint } from '@typescript-eslint/utils';
import type { PackageExportsResolverOptions } from '../../utils/package-exports';
import type { RuleDefinition } from '.';

export const MESSAGE_ID_MISSING_TAG = 'missingTag';

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/api-or-internal-tag.md
 */
export const apiOrInternalTagRule = <const>{
  meta: {
    type: 'suggestion',
    docs: {
      description: 'Require every exported declaration in a public-API source file to carry an `@api` or `@internal` JSDoc tag; a `@file` block carrying `@api`/`@internal` covers all symbols below it.',
      url: 'https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/api-or-internal-tag.md',
    },
    schema: [
      {
        type: 'object',
        additionalProperties: false,
        properties: {
          packageJsonPath: {
            type: 'string',
          },
          distRoot: {
            type: 'string',
          },
          srcRoot: {
            type: 'string',
          },
          srcExtensions: {
            type: 'array',
            items: {
              type: 'string',
            },
          },
        },
      },
    ],
    messages: {
      [MESSAGE_ID_MISSING_TAG]: '{{ kind }} `{{ name }}` is exported from a public-API source file and must carry an `@api` or `@internal` JSDoc tag.',
    },
  },
  create: (context) => {
    const options = <PackageExportsResolverOptions>(context.options[0] ?? {});

    if (!isPublicApiFile(options, context.cwd, context.filename)) {
      return {};
    }

    const sourceCode = <TSESLint.SourceCode><unknown>context.sourceCode;
    const fileComment = getFileLevelBlockComment(sourceCode);

    return buildExportVisitors(sourceCode, (symbol) => {
      if (getEffectiveVisibilityTag(symbol.comment, fileComment) !== undefined) {
        return;
      }

      context.report({
        node: symbol.anchor,
        messageId: MESSAGE_ID_MISSING_TAG,
        data: {
          kind: symbol.kind,
          name: symbol.name,
        },
      });
    });
  },
} satisfies RuleDefinition;
