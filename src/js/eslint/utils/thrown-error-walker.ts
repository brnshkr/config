/**
 * @internal @brnshkr/config/eslint
 */

import { readCommentText } from '#eslint/utils/jsdoc.ts';

import {
  ERROR_KIND_REJECTS,
  ERROR_KIND_THROWS,
  getDeclaredTypeNames,
  getThrownTypes,
  isUncheckedType,
  resolveDeclaredType,
} from '#eslint/utils/thrown-error-types.ts';

import { readOwnValue } from '#shared/utils/object.ts';

import type { ParserServicesWithTypeInformation, TSESLint, TSESTree } from '@typescript-eslint/utils';
import type ts from 'typescript';
import type { ErrorKind, ThrownError, ThrownType } from '#eslint/utils/thrown-error-types.ts';
import type { Maybe } from '#shared/types/core.ts';

export type FunctionNode = TSESTree.ArrowFunctionExpression
  | TSESTree.FunctionDeclaration
  | TSESTree.FunctionExpression;

export type RuleContext = TSESLint.RuleContext<string, unknown[]>;

interface WalkState {
  isAsync: boolean;
  isCaught: boolean;
  rejectName?: string;
}

interface WalkContext {
  sourceCode: TSESLint.SourceCode;
  services: Maybe<ParserServicesWithTypeInformation>;
  checker: Maybe<ts.TypeChecker>;
  isJavaScript: boolean;
  thrownErrors: ThrownError[];
  walk: (walkContext: WalkContext, node: TSESTree.Node, state: WalkState) => void;
}

const ARGUMENT_ERROR_NAME = 'TypeError';

const FUNCTION_TYPES = new Set([
  'ArrowFunctionExpression',
  'FunctionDeclaration',
  'FunctionExpression',
]);

const DOCUMENTED_PARENT_TYPES = new Set([
  'ExportDefaultDeclaration',
  'ExportNamedDeclaration',
  'MethodDefinition',
  'Property',
  'PropertyDefinition',
]);

const thrownErrorsByFunction = new WeakMap<FunctionNode, ThrownError[]>();
const createJavaScriptFilePattern = (): RegExp => /\.[cm]?jsx?$/v;
const isFunctionNode = (node: TSESTree.Node): node is FunctionNode => FUNCTION_TYPES.has(node.type);

/* eslint-disable ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum throughout this module */
export const isInlineCallback = (node: TSESTree.Node): boolean => {
  const { parent } = node;

  return (parent?.type === 'CallExpression' || parent?.type === 'NewExpression')
    && parent.arguments.includes(<TSESTree.CallExpressionArgument>node);
};

const isAwaitedOrReturned = (node: TSESTree.Node): boolean => {
  const { parent } = node;

  return parent?.type === 'AwaitExpression'
    || parent?.type === 'ReturnStatement'
    || (parent?.type === 'ArrowFunctionExpression' && parent.body === node);
};

const isPromiseReject = (node: TSESTree.CallExpression): boolean => node.callee.type === 'MemberExpression'
  && node.callee.object.type === 'Identifier'
  && node.callee.object.name === 'Promise'
  && node.callee.property.type === 'Identifier'
  && node.callee.property.name === 'reject';

export const getDocumentationHost = (node: TSESTree.Node): TSESTree.Node => {
  const { parent } = node;

  if (parent?.type === 'VariableDeclarator') {
    return getDocumentationHost(parent.parent);
  }

  return (parent !== undefined && DOCUMENTED_PARENT_TYPES.has(parent.type))
    ? getDocumentationHost(parent)
    : node;
};

const getThrowKind = (state: WalkState): ErrorKind => (state.isAsync
  ? ERROR_KIND_REJECTS
  : ERROR_KIND_THROWS);

const addThrownErrors = (walkContext: WalkContext, kind: ErrorKind, thrownTypes: ThrownType[]): void => {
  walkContext.thrownErrors.push(...thrownTypes.map((thrownType) => ({
    ...thrownType,
    kind,
  })));
};

const getExpressionTypes = (walkContext: WalkContext, node: Maybe<TSESTree.Node>): ThrownType[] => {
  const { checker, services } = walkContext;
  const tsNode = node === undefined ? undefined : services?.esTreeNodeToTSNodeMap.get(node);

  return getThrownTypes(
    checker,
    tsNode === undefined
      ? undefined
      : checker?.getTypeAtLocation(tsNode),
  );
};

const getCalleeDeclaration = (
  walkContext: WalkContext,
  node: TSESTree.CallExpression | TSESTree.NewExpression,
): Maybe<ts.SignatureDeclaration | ts.JSDocSignature> => {
  const { checker, services } = walkContext;
  const tsNode = <Maybe<ts.CallLikeExpression>>(services?.esTreeNodeToTSNodeMap.get(node));

  return tsNode === undefined
    ? undefined
    : checker?.getResolvedSignature(tsNode)?.getDeclaration();
};

const addCalleeErrors = (
  walkContext: WalkContext,
  node: TSESTree.CallExpression | TSESTree.NewExpression,
  state: WalkState,
): void => {
  const { checker, services } = walkContext;
  const declaration = getCalleeDeclaration(walkContext, node);

  if (checker === undefined || services === undefined || declaration === undefined) {
    return;
  }

  const variableDeclaration = <Maybe<ts.VariableDeclaration>>declaration.parent;
  const isVariableInitializer = variableDeclaration?.initializer === declaration;

  const comment = readCommentText(declaration)
    ?? (isVariableInitializer ? readCommentText(variableDeclaration.parent.parent) : undefined);

  const isNativeCallee = declaration.getSourceFile().fileName.includes('/node_modules/@types/');

  const hasUncheckedArgument = walkContext.isJavaScript && node.arguments.some(
    (argument) => isUncheckedType(checker.getTypeAtLocation(services.esTreeNodeToTSNodeMap.get(argument))),
  );

  const resolveDeclaredTypes = (kind: ErrorKind): ThrownType[] => getDeclaredTypeNames(comment, kind)
    .filter((typeName) => !isNativeCallee || hasUncheckedArgument || typeName !== ARGUMENT_ERROR_NAME)
    .map((typeName) => resolveDeclaredType(checker, declaration, typeName));

  addThrownErrors(walkContext, getThrowKind(state), resolveDeclaredTypes(ERROR_KIND_THROWS));

  if (isAwaitedOrReturned(node)) {
    addThrownErrors(walkContext, ERROR_KIND_REJECTS, resolveDeclaredTypes(ERROR_KIND_REJECTS));
  }
};

const walkChildren = (walkContext: WalkContext, node: TSESTree.Node, state: WalkState): void => {
  const childKeys = walkContext.sourceCode.visitorKeys[node.type] ?? [];

  for (const childKey of childKeys) {
    const child = readOwnValue(<Record<string, unknown>><unknown>node, childKey);
    const childNodes = Array.isArray(child) ? child : [child];

    for (const childNode of childNodes) {
      if (typeof childNode === 'object' && childNode && 'type' in childNode) {
        walkContext.walk(walkContext, <TSESTree.Node>childNode, state);
      }
    }
  }
};

const walkTry = (walkContext: WalkContext, node: TSESTree.TryStatement, state: WalkState): void => {
  walkContext.walk(walkContext, node.block, {
    ...state,
    isCaught: state.isCaught || (node.handler ?? undefined) !== undefined,
  });

  for (const clause of [node.handler, node.finalizer]) {
    if (clause) {
      walkContext.walk(walkContext, clause, state);
    }
  }
};

const walkCall = (walkContext: WalkContext, node: TSESTree.CallExpression, state: WalkState): void => {
  const isRejectCall = node.callee.type === 'Identifier' && node.callee.name === state.rejectName;

  if (isRejectCall || (isPromiseReject(node) && isAwaitedOrReturned(node))) {
    addThrownErrors(walkContext, ERROR_KIND_REJECTS, getExpressionTypes(walkContext, node.arguments[0]));
  } else if (!isPromiseReject(node)) {
    addCalleeErrors(walkContext, node, state);
  }

  walkChildren(walkContext, node, state);
};

const walkNew = (walkContext: WalkContext, node: TSESTree.NewExpression, state: WalkState): void => {
  const [executor] = node.arguments;
  const isPromise = node.callee.type === 'Identifier' && node.callee.name === 'Promise';

  if (!isPromise || executor === undefined || !isFunctionNode(executor)) {
    addCalleeErrors(walkContext, node, state);
    walkChildren(walkContext, node, state);
  } else if (isAwaitedOrReturned(node)) {
    const [, rejectParameter] = executor.params;

    walkContext.walk(walkContext, executor.body, {
      isAsync: true,
      isCaught: false,
      rejectName: rejectParameter?.type === 'Identifier'
        ? rejectParameter.name
        : undefined,
    });
  }
};

const walkNode = (walkContext: WalkContext, node: TSESTree.Node, state: WalkState): void => {
  if (isFunctionNode(node)) {
    if (isInlineCallback(node)) {
      walkNode(walkContext, node.body, {
        ...state,
        isAsync: node.async,
      });
    }
  } else if (node.type === 'TryStatement') {
    walkTry(walkContext, node, state);
  } else if (state.isCaught) {
    walkChildren(walkContext, node, state);
  } else if (node.type === 'ThrowStatement') {
    addThrownErrors(walkContext, getThrowKind(state), getExpressionTypes(walkContext, node.argument));
    walkChildren(walkContext, node, state);
  } else if (node.type === 'CallExpression') {
    walkCall(walkContext, node, state);
  } else if (node.type === 'NewExpression') {
    walkNew(walkContext, node, state);
  } else {
    walkChildren(walkContext, node, state);
  }
};
/* eslint-enable ts/no-unsafe-enum-comparison */

export const collectThrownErrors = (context: RuleContext, node: FunctionNode): ThrownError[] => {
  const cachedErrors = thrownErrorsByFunction.get(node);

  if (cachedErrors !== undefined) {
    return cachedErrors;
  }

  const services = <Maybe<ParserServicesWithTypeInformation>><unknown>context.sourceCode.parserServices;
  const program = <Maybe<ts.Program>>services?.program;

  const walkContext = {
    sourceCode: context.sourceCode,
    services: program === undefined ? undefined : services,
    checker: program?.getTypeChecker(),
    isJavaScript: createJavaScriptFilePattern().test(context.filename),
    thrownErrors: <ThrownError[]>[],
    walk: walkNode,
  } satisfies WalkContext;

  walkNode(walkContext, node.body, {
    isAsync: node.async,
    isCaught: false,
  });

  thrownErrorsByFunction.set(node, walkContext.thrownErrors);

  return walkContext.thrownErrors;
};
