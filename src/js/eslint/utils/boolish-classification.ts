import { toPosix } from '../../shared/utils/filesystem';

import type { ParserServicesWithTypeInformation, TSESTree } from '@typescript-eslint/utils';
import type ts from 'typescript';
import type { Maybe } from '../../shared/types/core';

export const TYPE_CLASSIFICATIONS = <const>{
  BOOL: 'bool',
  NON_BOOL: 'non-bool',
  UNKNOWN: 'unknown',
};

export type TypeClassification = typeof TYPE_CLASSIFICATIONS[keyof typeof TYPE_CLASSIFICATIONS];

const BOOLEAN_TYPE_NAMES = new Set([
  'boolean',
  'false',
  'true',
]);

const NULLISH_TYPE_NAMES = new Set([
  'null',
  'undefined',
]);

const UNRESOLVED_TYPE_NAMES = new Set([
  'any',
  'error',
  'never',
  'unknown',
]);

const BOOLEAN_TYPE_NODES = new Set([
  'TSBooleanKeyword',
  'TSTypePredicate',
]);

const NULLISH_TYPE_NODES = new Set([
  'TSNullKeyword',
  'TSUndefinedKeyword',
]);

const UNRESOLVED_TYPE_NODES = new Set([
  'TSAnyKeyword',
  'TSConditionalType',
  'TSImportType',
  'TSIndexedAccessType',
  'TSInferType',
  'TSIntersectionType',
  'TSMappedType',
  'TSNeverKeyword',
  'TSThisType',
  'TSTypeOperator',
  'TSTypeQuery',
  'TSTypeReference',
  'TSUnknownKeyword',
]);

const NON_BOOLEAN_EXPRESSIONS = new Set([
  'ArrayExpression',
  'NewExpression',
  'ObjectExpression',
  'TemplateLiteral',
]);

const UNWRAPPED_EXPRESSIONS = new Set([
  'TSAsExpression',
  'TSNonNullExpression',
  'TSSatisfiesExpression',
  'TSTypeAssertion',
]);

const BOOLEAN_BINARY_OPERATORS = new Set([
  '!=',
  '!==',
  '<',
  '<=',
  '==',
  '===',
  '>',
  '>=',
  'in',
  'instanceof',
]);

const BOOLEAN_CONSTRUCTOR_NAME = 'Boolean';
const PROMISE_TYPE_NAME = 'Promise';
const EXTERNAL_PATH_SEGMENT = '/node_modules/';

export const combineClassifications = (classifications: TypeClassification[]): TypeClassification => {
  if (classifications.length === 0) {
    return TYPE_CLASSIFICATIONS.UNKNOWN;
  }

  if (classifications.every((classification) => classification === TYPE_CLASSIFICATIONS.BOOL)) {
    return TYPE_CLASSIFICATIONS.BOOL;
  }

  return classifications.every((classification) => classification === TYPE_CLASSIFICATIONS.NON_BOOL)
    ? TYPE_CLASSIFICATIONS.NON_BOOL
    : TYPE_CLASSIFICATIONS.UNKNOWN;
};

const getTypeName = (checker: ts.TypeChecker, type: ts.Type): string => checker.typeToString(
  checker.getBaseTypeOfLiteralType(type),
);

const classifyPlainType = (checker: ts.TypeChecker, type: ts.Type): TypeClassification => {
  const symbol = type.getSymbol();

  if (type.isTypeParameter()) {
    return TYPE_CLASSIFICATIONS.UNKNOWN;
  }

  if (symbol !== undefined) {
    return TYPE_CLASSIFICATIONS.NON_BOOL;
  }

  const typeName = getTypeName(checker, type);

  if (BOOLEAN_TYPE_NAMES.has(typeName)) {
    return TYPE_CLASSIFICATIONS.BOOL;
  }

  return UNRESOLVED_TYPE_NAMES.has(typeName)
    ? TYPE_CLASSIFICATIONS.UNKNOWN
    : TYPE_CLASSIFICATIONS.NON_BOOL;
};

export const classifyType = (checker: ts.TypeChecker, type: ts.Type): TypeClassification => {
  if (!type.isUnion()) {
    return classifyPlainType(checker, type);
  }

  return combineClassifications(type.types
    .filter((member) => !NULLISH_TYPE_NAMES.has(getTypeName(checker, member)))
    .map((member) => classifyPlainType(checker, member)));
};

const unwrapPromiseType = (checker: ts.TypeChecker, type: ts.Type): ts.Type => (
  type.getSymbol()?.getName() === PROMISE_TYPE_NAME
    ? (checker.getTypeArguments(<ts.TypeReference>type)[0] ?? type)
    : type
);

export const resolveReturnType = (checker: ts.TypeChecker, type: ts.Type): Maybe<ts.Type> => {
  const [signature] = type.getCallSignatures();

  return signature === undefined ? undefined : unwrapPromiseType(checker, signature.getReturnType());
};

const isExternalSymbol = (symbol: ts.Symbol): boolean => symbol.declarations?.some(
  (declaration) => toPosix(declaration.getSourceFile().fileName).includes(EXTERNAL_PATH_SEGMENT),
) ?? true;

const resolveInstanceType = (type: ts.Type): ts.Type => type.getConstructSignatures()[0]?.getReturnType() ?? type;

export const hasExternalUpstreamMember = (
  services: ParserServicesWithTypeInformation,
  enclosingClass: TSESTree.ClassDeclaration | TSESTree.ClassExpression,
  name: string,
): boolean => [
  ...(enclosingClass.superClass === null ? [] : [enclosingClass.superClass]),
  ...enclosingClass.implements,
].some((heritageNode) => {
  const member = resolveInstanceType(services.getTypeAtLocation(heritageNode)).getProperty(name);

  return member !== undefined && isExternalSymbol(member);
});

/* eslint-disable ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum throughout this module */

export const classifyTypeAnnotation = (node: TSESTree.TypeNode): TypeClassification => {
  if (node.type === 'TSUnionType') {
    return combineClassifications(node.types
      .filter((member) => !NULLISH_TYPE_NODES.has(member.type))
      .map((member) => classifyTypeAnnotation(member)));
  }

  if (node.type === 'TSLiteralType') {
    return (node.literal.type === 'Literal' && typeof node.literal.value === 'boolean')
      ? TYPE_CLASSIFICATIONS.BOOL
      : TYPE_CLASSIFICATIONS.NON_BOOL;
  }

  if (BOOLEAN_TYPE_NODES.has(node.type)) {
    return TYPE_CLASSIFICATIONS.BOOL;
  }

  return UNRESOLVED_TYPE_NODES.has(node.type)
    ? TYPE_CLASSIFICATIONS.UNKNOWN
    : TYPE_CLASSIFICATIONS.NON_BOOL;
};

const classifyBooleanOperator = (node: TSESTree.Expression): Maybe<TypeClassification> => {
  if (node.type === 'UnaryExpression') {
    return node.operator === '!' ? TYPE_CLASSIFICATIONS.BOOL : TYPE_CLASSIFICATIONS.NON_BOOL;
  }

  if (node.type === 'BinaryExpression') {
    return BOOLEAN_BINARY_OPERATORS.has(node.operator)
      ? TYPE_CLASSIFICATIONS.BOOL
      : TYPE_CLASSIFICATIONS.NON_BOOL;
  }

  if (node.type === 'CallExpression' && node.callee.type === 'Identifier') {
    return node.callee.name === BOOLEAN_CONSTRUCTOR_NAME ? TYPE_CLASSIFICATIONS.BOOL : undefined;
  }

  return undefined;
};

export const classifyExpression = (node: TSESTree.Expression): TypeClassification => {
  if (node.type === 'Literal') {
    return typeof node.value === 'boolean' ? TYPE_CLASSIFICATIONS.BOOL : TYPE_CLASSIFICATIONS.NON_BOOL;
  }

  if (UNWRAPPED_EXPRESSIONS.has(node.type)) {
    return classifyExpression((<TSESTree.TSAsExpression>node).expression);
  }

  return NON_BOOLEAN_EXPRESSIONS.has(node.type)
    ? TYPE_CLASSIFICATIONS.NON_BOOL
    : (classifyBooleanOperator(node) ?? TYPE_CLASSIFICATIONS.UNKNOWN);
};
/* eslint-enable ts/no-unsafe-enum-comparison -- Restore rule */
