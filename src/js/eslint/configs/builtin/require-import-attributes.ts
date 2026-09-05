/**
 * @internal @brnshkr/config/eslint
 */

import type { TSESTree } from '@typescript-eslint/utils';
import type { RuleDefinition } from '.';

export const MESSAGE_ID_MISSING_WITH_KEYWORD = 'missingWithKeyword';
export const MESSAGE_ID_MISSING_TYPE_PROPERTY = 'missingTypeProperty';
export const MESSAGE_ID_UNEXPECTED_TYPE_VALUE = 'unexpectedTypeValue';

const FILE_TYPE_MAP: Record<string, string> = <const>{
  '.json': 'json',
  '.css': 'css',
  '.svg': 'svg',
  '.png': 'image',
  '.jpg': 'image',
  '.jpeg': 'image',
  '.txt': 'text',
  '.oct': 'bytes',
  '.wasm': 'webassembly',
};

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/require-import-attributes.md
 */
export const requireImportAttributesRule = <const>{
  meta: {
    type: 'problem',
    docs: {
      description: 'Require non-JavaScript imports (e.g. .json and .css) to include import attributes.',
      url: 'https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/require-import-attributes.md',
    },
    messages: {
      [MESSAGE_ID_MISSING_WITH_KEYWORD]: 'Non-JavaScript import (\'{{ extension }}\') requires an import attributes object with the \'type\' property set to \'{{ expectedValue }}\'.',
      [MESSAGE_ID_MISSING_TYPE_PROPERTY]: 'Import attributes for non-JavaScript imports must include the \'type\' property.',
      [MESSAGE_ID_UNEXPECTED_TYPE_VALUE]: 'Import attribute \'type\' for \'{{ file }}\' must be \'{{ expectedValue }}\'.',
    },
  },
  create: (context) => ({
    ImportDeclaration: (node: TSESTree.ImportDeclaration): void => {
      const sourceValue = node.source.value;
      const extensionIndex = sourceValue.lastIndexOf('.');

      if (extensionIndex === -1) {
        return;
      }

      const extension = sourceValue.slice(extensionIndex).toLowerCase();
      const expectedValue = FILE_TYPE_MAP[extension];

      if (expectedValue === undefined) {
        return;
      }

      const { attributes } = node;

      if (attributes.length === 0) {
        context.report({
          node,
          messageId: MESSAGE_ID_MISSING_WITH_KEYWORD,
          data: {
            extension,
            expectedValue,
          },
        });

        return;
      }

      /* eslint-disable ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum */
      const typeProperty = attributes.find(
        (attribute) => (
          attribute.key.type === 'Identifier'
          && 'name' in attribute.key
          && attribute.key.name === 'type'
        ) || (
          attribute.key.type === 'Literal'
          && 'value' in attribute.key
          && attribute.key.value === 'type'
        ),
      );
      /* eslint-enable ts/no-unsafe-enum-comparison -- Restore rule */

      if (!typeProperty) {
        context.report({
          node,
          messageId: MESSAGE_ID_MISSING_TYPE_PROPERTY,
        });

        return;
      }

      if (typeProperty.value.value !== expectedValue) {
        context.report({
          node,
          messageId: MESSAGE_ID_UNEXPECTED_TYPE_VALUE,
          data: {
            expectedValue,
            file: sourceValue,
          },
        });
      }
    },
  }),
} satisfies RuleDefinition;
