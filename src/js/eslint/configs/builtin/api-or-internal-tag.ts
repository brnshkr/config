import { buildExportVisitors } from '../../utils/exports';

import {
  findFileLevelComment,
  getEffectiveVisibilityTag,
  hasConflictingVisibilityTags,
} from '../../utils/jsdoc';

import { isPublicApiFile } from '../../utils/public-api';

import type { TSESLint } from '@typescript-eslint/utils';
import type { PackageExportsResolverOptions } from '../../utils/package-exports';
import type { RuleDefinition } from '.';

export const MESSAGE_ID_MISSING_TAG = 'missingTag';
export const MESSAGE_ID_CONFLICTING_TAGS = 'conflictingTags';
export const MESSAGE_ID_CONFLICTING_FILE_TAGS = 'conflictingFileTags';

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/api-or-internal-tag.md
 */
export const apiOrInternalTagRule = <const>{
  meta: {
    type: 'suggestion',
    docs: {
      description: 'Require every exported declaration in a public-API source file to carry either an `@api` or an `@internal` tag, and never both; a file-level docblock carrying either tag covers all symbols below it.',
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
      [MESSAGE_ID_CONFLICTING_TAGS]: '{{ kind }} `{{ name }}` must declare exactly one visibility, but carries both `@api` and `@internal`.',
      [MESSAGE_ID_CONFLICTING_FILE_TAGS]: 'The file-level docblock must declare exactly one visibility, but carries both `@api` and `@internal`.',
    },
  },
  create: (context) => {
    const options = <PackageExportsResolverOptions>(context.options[0] ?? {});
    const sourceCode = <TSESLint.SourceCode><unknown>context.sourceCode;
    const fileCommentNode = findFileLevelComment(sourceCode);
    const fileComment = fileCommentNode === undefined ? undefined : `/*${fileCommentNode.value}*/`;
    const isInPublicApiFile = isPublicApiFile(options, context.cwd, context.filename);

    return {
      ...buildExportVisitors(sourceCode, (symbol) => {
        if (hasConflictingVisibilityTags(symbol.comment)) {
          context.report({
            node: symbol.anchor,
            messageId: MESSAGE_ID_CONFLICTING_TAGS,
            data: {
              kind: symbol.kind,
              name: symbol.name,
            },
          });

          return;
        }

        if (!isInPublicApiFile || getEffectiveVisibilityTag(symbol.comment, fileComment) !== undefined) {
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
      }),
      'Program:exit': (): void => {
        if (fileCommentNode !== undefined && hasConflictingVisibilityTags(fileComment)) {
          context.report({
            loc: fileCommentNode.loc,
            messageId: MESSAGE_ID_CONFLICTING_FILE_TAGS,
          });
        }
      },
    };
  },
} satisfies RuleDefinition;
