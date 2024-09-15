import { objectFreeze, objectFromEntries, objectKeys } from '../../shared/utils/object';
import { packageOrganization, packageOrganizationUpper, packageVersion } from '../../shared/utils/package-json';
import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { GLOB_SCRIPT_FILES } from '../utils/globs';

import type { TSESTree } from '@typescript-eslint/utils';
import type { ESLint } from 'eslint';
import type { Config } from '../types/config';

type ExtractValueTypeFromRecord<TRecord> = TRecord extends Record<string, infer U> ? U : never;
type RuleDefinition = ExtractValueTypeFromRecord<ESLint.Plugin['rules']>;

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

const MESSAGE_ID_MISSING_WITH_KEYWORD = 'missingWithKeyword';
const MESSAGE_ID_MISSING_TYPE_PROPERTY = 'missingTypeProperty';
const MESSAGE_ID_WRONG_TYPE_VALUE = 'wrongTypeValue';

const requireImportAttributesRule = <const>{
  meta: {
    type: 'problem',
    docs: {
      description: 'Require non-JavaScript imports (e.g. .json and .css) to include import attributes.',
    },
    messages: {
      [MESSAGE_ID_MISSING_WITH_KEYWORD]: 'Non-JavaScript import (\'{{ extension }}\') requires an import attributes object with the \'type\' property set to \'{{ expectedValue }}\'.',
      [MESSAGE_ID_MISSING_TYPE_PROPERTY]: 'Import attributes for non-JavaScript imports must include the \'type\' property.',
      [MESSAGE_ID_WRONG_TYPE_VALUE]: 'Import attribute \'type\' for \'{{ file }}\' must be \'{{ expectedValue }}\'.',
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

      /* eslint-disable ts/no-unsafe-enum-comparison -- Usage of strings is desired here to not have an explicit dependency on typescript-eslint */
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
          messageId: MESSAGE_ID_WRONG_TYPE_VALUE,
          data: {
            expectedValue,
            file: sourceValue,
          },
        });
      }
    },
  }),
} satisfies RuleDefinition;

const RULE_DEFINITIONS = <const>{
  'require-import-attributes': requireImportAttributesRule,
} satisfies Record<string, RuleDefinition>;

const RULES = objectFreeze(objectFromEntries(objectKeys(RULE_DEFINITIONS).map(
  (ruleName) => <const>[`${packageOrganization}/${ruleName}`, 'error'],
)));

const builtin = (): Config[] => [
  {
    name: buildConfigName(MAIN_SCOPES[packageOrganizationUpper], SUB_SCOPES.SETUP),
    plugins: {
      [packageOrganization]: {
        meta: {
          name: packageOrganization,
          version: packageVersion,
        },
        rules: RULE_DEFINITIONS,
      },
    },
  },
  {
    name: buildConfigName(MAIN_SCOPES[packageOrganizationUpper], SUB_SCOPES.RULES),
    files: GLOB_SCRIPT_FILES,
    rules: RULES,
  },
];

export const builtinConfig = {
  [packageOrganization]: builtin,
};
