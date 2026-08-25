/**
 * @internal @brnshkr/config/eslint
 */

import type { TSESTree } from '@typescript-eslint/utils';
import type { RuleDefinition } from '.';

export const MESSAGE_ID_MISSING_SUFFIX = 'missingSuffix';
export const INTERFACE_SUFFIX = 'Interface';

/* eslint-disable ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum throughout this module */
const getImplementedName = (expression: TSESTree.Expression): string => {
  if (expression.type === 'Identifier') {
    return expression.name;
  }

  if (expression.type === 'MemberExpression' && expression.property.type === 'Identifier') {
    return expression.property.name;
  }

  return '';
};
/* eslint-enable ts/no-unsafe-enum-comparison -- Restore rule */

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/interface-suffix.md
 */
export const interfaceSuffixRule = <const>{
  meta: {
    type: 'suggestion',
    docs: {
      description: 'Require classes that implement a single `*Interface` to end with the matching suffix.',
      url: 'https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/interface-suffix.md',
    },
    messages: {
      [MESSAGE_ID_MISSING_SUFFIX]: 'Class `{{ name }}` implements `{{ interfaceName }}` and must end with suffix `{{ suffix }}`.',
    },
  },
  create: (context) => {
    const checkHeritage = (node: TSESTree.ClassDeclaration | TSESTree.ClassExpression): void => {
      if (!node.id) {
        return;
      }

      const implementedNames = node.implements
        .map((implemented) => getImplementedName(implemented.expression))
        .filter((name) => name.length > INTERFACE_SUFFIX.length && name.endsWith(INTERFACE_SUFFIX));

      if (implementedNames.length !== 1) {
        return;
      }

      const [interfaceName = ''] = implementedNames;
      const suffix = interfaceName.slice(0, -INTERFACE_SUFFIX.length);

      if (node.id.name.endsWith(suffix)) {
        return;
      }

      context.report({
        node: node.id,
        messageId: MESSAGE_ID_MISSING_SUFFIX,
        data: {
          name: node.id.name,
          interfaceName,
          suffix,
        },
      });
    };

    /* eslint-disable ts/naming-convention -- Visitor keys mirror AST `Node.type` literal values */
    return {
      ClassDeclaration: checkHeritage,
      ClassExpression: checkHeritage,
    };
    /* eslint-enable ts/naming-convention -- Restore rule */
  },
} satisfies RuleDefinition;
