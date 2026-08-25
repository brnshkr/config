/**
 * @internal @brnshkr/config/eslint
 */

import type { TSESTree } from '@typescript-eslint/utils';
import type { Maybe } from '../../shared/types/core';

export type FunctionLikeNode = TSESTree.ArrowFunctionExpression
  | TSESTree.FunctionDeclaration
  | TSESTree.FunctionExpression
  | TSESTree.TSDeclareFunction
  | TSESTree.TSMethodSignature;

export type MethodLikeNode = TSESTree.MethodDefinition
  | TSESTree.TSAbstractMethodDefinition;

export type FunctionDeclarationLikeNode = TSESTree.ArrowFunctionExpression
  | TSESTree.FunctionDeclaration
  | TSESTree.FunctionExpression
  | TSESTree.TSDeclareFunction
  | TSESTree.VariableDeclarator;

export interface FunctionShape {
  parameters: TSESTree.Parameter[];
  returnType: Maybe<TSESTree.TSTypeAnnotation>;
}

/* eslint-disable ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum throughout this module */
export const isFluentReturn = (
  returnType: Maybe<TSESTree.TSTypeAnnotation>,
): boolean => returnType?.typeAnnotation.type === 'TSThisType';

export const isVoidLikeReturn = (
  returnType: Maybe<TSESTree.TSTypeAnnotation>,
): boolean => returnType === undefined
  || returnType.typeAnnotation.type === 'TSVoidKeyword'
  || returnType.typeAnnotation.type === 'TSNeverKeyword';

export const resolveParameterIdentifier = (parameter: TSESTree.Parameter): Maybe<TSESTree.Identifier> => {
  if (parameter.type === 'Identifier') {
    return parameter;
  }

  if (parameter.type === 'AssignmentPattern' && parameter.left.type === 'Identifier') {
    return parameter.left;
  }

  if (parameter.type === 'RestElement' && parameter.argument.type === 'Identifier') {
    return parameter.argument;
  }

  if (parameter.type === 'TSParameterProperty') {
    return resolveParameterIdentifier(parameter.parameter);
  }

  return undefined;
};

export const getParameterName = (
  parameter: TSESTree.Parameter,
): Maybe<string> => resolveParameterIdentifier(parameter)?.name;

export const isAccessibleMethod = (
  method: MethodLikeNode,
): boolean => method.accessibility !== 'private'
  && method.accessibility !== 'protected'
  && method.key.type !== 'PrivateIdentifier';

export const isAbstractMethod = (
  method: MethodLikeNode,
): boolean => method.type === 'TSAbstractMethodDefinition';

export const isClassMethodLike = (
  node: TSESTree.ClassElement,
): node is MethodLikeNode => node.type === 'MethodDefinition' || node.type === 'TSAbstractMethodDefinition';

export const getNamedKeyText = (key: TSESTree.PropertyName | TSESTree.PrivateIdentifier): string => {
  if (key.type === 'Identifier' || key.type === 'PrivateIdentifier') {
    return key.name;
  }

  if (key.type === 'Literal') {
    return String(key.value);
  }

  return '<computed>';
};

export const isFunctionInitializer = (
  candidate: TSESTree.Expression | null,
): candidate is TSESTree.ArrowFunctionExpression | TSESTree.FunctionExpression => candidate?.type === 'ArrowFunctionExpression'
  || candidate?.type === 'FunctionExpression';

export const resolveFunctionShape = (
  declaration: FunctionDeclarationLikeNode,
): Maybe<FunctionShape> => {
  if (declaration.type === 'VariableDeclarator') {
    if (!isFunctionInitializer(declaration.init)) {
      return undefined;
    }

    return {
      parameters: declaration.init.params,
      returnType: declaration.init.returnType,
    };
  }

  return {
    parameters: declaration.params,
    returnType: declaration.returnType,
  };
};
/* eslint-enable ts/no-unsafe-enum-comparison -- Restore rule */
