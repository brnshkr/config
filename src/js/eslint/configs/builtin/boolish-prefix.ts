/**
 * @internal @brnshkr/config/eslint
 */

import { resolveParameterIdentifier } from '../../utils/ast';

import {
  classifyExpression,
  classifyType,
  classifyTypeAnnotation,
  combineClassifications,
  hasExternalUpstreamMember,
  resolveReturnType,
  TYPE_CLASSIFICATIONS,
} from '../../utils/boolish-classification';

import {
  getPrefixesForKind,
  getReservedToken,
  isBoolishName,
  KIND_CONSTANT,
  KIND_FUNCTION,
  KIND_METHOD,
  KIND_PARAMETER,
  KIND_PROPERTY,
  KIND_VARIABLE,
} from '../../utils/boolish-prefixes';

import type { ParserServicesWithTypeInformation, TSESLint, TSESTree } from '@typescript-eslint/utils';
import type ts from 'typescript';
import type { Maybe } from '../../../shared/types/core';
import type { TypeClassification } from '../../utils/boolish-classification';
import type { RuleDefinition } from '.';

export const MESSAGE_ID_MISSING_PREFIX = 'missingPrefix';
export const MESSAGE_ID_UNEXPECTED_PREFIX = 'unexpectedPrefix';

const CLASS_MEMBER_NODES = new Set([
  'MethodDefinition',
  'PropertyDefinition',
  'TSAbstractMethodDefinition',
  'TSAbstractPropertyDefinition',
]);

const CALLABLE_INITIALIZERS = new Set([
  'ArrowFunctionExpression',
  'FunctionExpression',
]);

const RETURN_OWNER_NODES = new Set([
  'ArrowFunctionExpression',
  'FunctionDeclaration',
  'FunctionExpression',
]);

const PARAMETER_OWNER_SELECTOR = `${[
  'ArrowFunctionExpression',
  'FunctionDeclaration',
  'FunctionExpression',
  'TSDeclareFunction',
  'TSEmptyBodyFunctionExpression',
  'TSFunctionType',
  'TSMethodSignature',
].join(', ')}:exit`;

type NameNode = TSESTree.Identifier
  | TSESTree.PrivateIdentifier;

type CallableNode = TSESTree.ArrowFunctionExpression
  | TSESTree.FunctionDeclaration
  | TSESTree.FunctionExpression
  | TSESTree.TSDeclareFunction
  | TSESTree.TSEmptyBodyFunctionExpression
  | TSESTree.TSFunctionType
  | TSESTree.TSMethodSignature;

type ClassMemberNode = TSESTree.MethodDefinition
  | TSESTree.PropertyDefinition
  | TSESTree.TSAbstractMethodDefinition
  | TSESTree.TSAbstractPropertyDefinition;

interface SymbolTarget {
  nameNode: NameNode;
  valueKind: string;
  callableKind: string;
  callable?: CallableNode;
  typeAnnotation?: Maybe<TSESTree.TSTypeAnnotation>;
  initializer?: Maybe<TSESTree.Expression> | null;
}

type RuleVisitors = ReturnType<NonNullable<RuleDefinition>['create']>;

interface Resolution {
  classification: TypeClassification;
  isCallable: boolean;
}

interface RuleContext {
  report: (nameNode: NameNode, messageId: string, data: Record<string, string>) => void;
  services: Maybe<ParserServicesWithTypeInformation>;
  checker: Maybe<ts.TypeChecker>;
  returnClassifications: Map<TSESTree.Node, TypeClassification[]>;
}

/* eslint-disable ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum throughout this module */

const resolveNameNode = (node: TSESTree.Node): Maybe<NameNode> => (
  (node.type === 'Identifier' || node.type === 'PrivateIdentifier') ? node : undefined
);

const isSetterMember = (node: ClassMemberNode): boolean => {
  if (node.type !== 'MethodDefinition' && node.type !== 'TSAbstractMethodDefinition') {
    return false;
  }

  return node.kind === 'set';
};

const isSkippedMember = (ruleContext: RuleContext, node: TSESTree.Node): boolean => {
  if (!CLASS_MEMBER_NODES.has(node.type)) {
    return false;
  }

  const member = <ClassMemberNode>node;
  const nameNode = resolveNameNode(member.key);

  if (nameNode === undefined || isSetterMember(member)) {
    return true;
  }

  return ruleContext.services !== undefined
    && hasExternalUpstreamMember(ruleContext.services, member.parent.parent, nameNode.name);
};

const findEnclosingCallable = (node: TSESTree.Node): Maybe<TSESTree.Node> => {
  let current = node.parent;

  while (current !== undefined && !RETURN_OWNER_NODES.has(current.type)) {
    current = current.parent;
  }

  return current;
};

const resolveCallableResult = (ruleContext: RuleContext, callable: CallableNode): TypeClassification => {
  if (callable.returnType) {
    return classifyTypeAnnotation(callable.returnType.typeAnnotation);
  }

  if (callable.type === 'ArrowFunctionExpression' && callable.body.type !== 'BlockStatement') {
    return classifyExpression(callable.body);
  }

  return combineClassifications(ruleContext.returnClassifications.get(callable) ?? []);
};

const resolveSyntacticCallable = (
  target: SymbolTarget,
  annotation: Maybe<TSESTree.TypeNode>,
): Maybe<CallableNode> => {
  if (target.callable !== undefined) {
    return target.callable;
  }

  if (annotation?.type === 'TSFunctionType') {
    return annotation;
  }

  return CALLABLE_INITIALIZERS.has(target.initializer?.type ?? '')
    ? <TSESTree.ArrowFunctionExpression>target.initializer
    : undefined;
};

const resolveHeldClassification = (
  target: SymbolTarget,
  annotation: Maybe<TSESTree.TypeNode>,
): TypeClassification => {
  if (annotation !== undefined) {
    return classifyTypeAnnotation(annotation);
  }

  return (target.initializer === undefined || target.initializer === null)
    ? TYPE_CLASSIFICATIONS.UNKNOWN
    : classifyExpression(target.initializer);
};

const resolveFromSyntax = (ruleContext: RuleContext, target: SymbolTarget): Resolution => {
  const annotation = target.typeAnnotation?.typeAnnotation;
  const callable = resolveSyntacticCallable(target, annotation);

  if (callable === undefined) {
    return {
      classification: resolveHeldClassification(target, annotation),
      isCallable: false,
    };
  }

  return {
    classification: resolveCallableResult(ruleContext, callable),
    isCallable: true,
  };
};

/* eslint-enable ts/no-unsafe-enum-comparison -- Restore rule */

const resolveFromTypes = (
  services: ParserServicesWithTypeInformation,
  checker: ts.TypeChecker,
  nameNode: NameNode,
): Resolution => {
  const type = services.getTypeAtLocation(nameNode);
  const returnType = resolveReturnType(checker, type);

  return {
    classification: classifyType(checker, returnType ?? type),
    isCallable: returnType !== undefined,
  };
};

const checkTarget = (ruleContext: RuleContext, target: SymbolTarget): void => {
  const { checker, services } = ruleContext;

  const { classification, isCallable } = (services === undefined || checker === undefined)
    ? resolveFromSyntax(ruleContext, target)
    : resolveFromTypes(services, checker, target.nameNode);

  const kind = isCallable ? target.callableKind : target.valueKind;
  const { name } = target.nameNode;

  if (classification === TYPE_CLASSIFICATIONS.BOOL) {
    if (!isBoolishName(name, kind)) {
      ruleContext.report(target.nameNode, MESSAGE_ID_MISSING_PREFIX, {
        kind,
        name,
        prefixes: getPrefixesForKind(kind).join(', '),
      });
    }

    return;
  }

  const reservedToken = classification === TYPE_CLASSIFICATIONS.NON_BOOL
    ? getReservedToken(name, kind)
    : undefined;

  if (reservedToken !== undefined) {
    ruleContext.report(target.nameNode, MESSAGE_ID_UNEXPECTED_PREFIX, {
      kind,
      name,
      prefix: reservedToken,
    });
  }
};

const checkParameters = (ruleContext: RuleContext, node: CallableNode): void => {
  if (isSkippedMember(ruleContext, node.parent)) {
    return;
  }

  for (const parameter of node.params) {
    const nameNode = resolveParameterIdentifier(parameter);

    if (nameNode !== undefined) {
      checkTarget(ruleContext, {
        nameNode,
        // eslint-disable-next-line ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum
        valueKind: parameter.type === 'TSParameterProperty' ? KIND_PROPERTY : KIND_PARAMETER,
        callableKind: KIND_FUNCTION,
        typeAnnotation: nameNode.typeAnnotation,
        // eslint-disable-next-line ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum
        initializer: parameter.type === 'AssignmentPattern' ? parameter.right : undefined,
      });
    }
  }
};

/* eslint-disable ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum throughout this module */

const checkClassMember = (ruleContext: RuleContext, node: ClassMemberNode): void => {
  const nameNode = resolveNameNode(node.key);

  if (nameNode === undefined || isSkippedMember(ruleContext, node)) {
    return;
  }

  if (node.type === 'PropertyDefinition' || node.type === 'TSAbstractPropertyDefinition') {
    checkTarget(ruleContext, {
      nameNode,
      valueKind: KIND_PROPERTY,
      callableKind: KIND_METHOD,
      typeAnnotation: node.typeAnnotation,
      initializer: node.value,
    });

    return;
  }

  if (node.kind !== 'constructor') {
    checkTarget(ruleContext, {
      nameNode,
      valueKind: KIND_PROPERTY,
      callableKind: node.kind === 'get' ? KIND_PROPERTY : KIND_METHOD,
      callable: node.value,
    });
  }
};

const collectReturnClassification = (ruleContext: RuleContext, node: TSESTree.ReturnStatement): void => {
  const callable = ruleContext.services === undefined ? findEnclosingCallable(node) : undefined;

  if (callable === undefined) {
    return;
  }

  ruleContext.returnClassifications.set(callable, [
    ...ruleContext.returnClassifications.get(callable) ?? [],
    node.argument === null ? TYPE_CLASSIFICATIONS.NON_BOOL : classifyExpression(node.argument),
  ]);
};

const buildVisitors = (ruleContext: RuleContext): RuleVisitors => ({
  [PARAMETER_OWNER_SELECTOR]: (node: CallableNode): void => {
    checkParameters(ruleContext, node);
  },
  'FunctionDeclaration, TSDeclareFunction:exit': (
    node: TSESTree.FunctionDeclaration | TSESTree.TSDeclareFunction,
  ): void => {
    if (node.id !== null) {
      checkTarget(ruleContext, {
        nameNode: node.id,
        valueKind: KIND_FUNCTION,
        callableKind: KIND_FUNCTION,
        callable: node,
      });
    }
  },
  'MethodDefinition, PropertyDefinition, TSAbstractMethodDefinition, TSAbstractPropertyDefinition:exit': (
    node: ClassMemberNode,
  ): void => {
    checkClassMember(ruleContext, node);
  },
  'ReturnStatement:exit': (node: TSESTree.ReturnStatement): void => {
    collectReturnClassification(ruleContext, node);
  },
  'TSEnumMember:exit': (node: TSESTree.TSEnumMember): void => {
    const nameNode = resolveNameNode(node.id);

    if (nameNode !== undefined) {
      checkTarget(ruleContext, {
        nameNode,
        valueKind: KIND_CONSTANT,
        callableKind: KIND_CONSTANT,
        initializer: node.initializer,
      });
    }
  },
  'TSMethodSignature:exit': (node: TSESTree.TSMethodSignature): void => {
    const nameNode = resolveNameNode(node.key);

    if (nameNode !== undefined) {
      checkTarget(ruleContext, {
        nameNode,
        valueKind: KIND_METHOD,
        callableKind: KIND_METHOD,
        callable: node,
      });
    }
  },
  'TSPropertySignature:exit': (node: TSESTree.TSPropertySignature): void => {
    const nameNode = resolveNameNode(node.key);

    if (nameNode !== undefined) {
      checkTarget(ruleContext, {
        nameNode,
        valueKind: KIND_PROPERTY,
        callableKind: KIND_METHOD,
        typeAnnotation: node.typeAnnotation,
      });
    }
  },
  'VariableDeclarator:exit': (node: TSESTree.VariableDeclarator): void => {
    if (node.id.type === 'Identifier') {
      checkTarget(ruleContext, {
        nameNode: node.id,
        valueKind: KIND_VARIABLE,
        callableKind: KIND_FUNCTION,
        typeAnnotation: node.id.typeAnnotation,
        initializer: node.init,
      });
    }
  },
});
/* eslint-enable ts/no-unsafe-enum-comparison -- Restore rule */

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/boolish-prefix.md
 */
export const boolishPrefixRule = <const>{
  meta: {
    type: 'suggestion',
    docs: {
      description: 'Keep boolean-ness and names aligned in both directions.',
      url: 'https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/boolish-prefix.md',
    },
    messages: {
      [MESSAGE_ID_MISSING_PREFIX]: '{{ kind }} name `{{ name }}` must have one of the following prefixes: {{ prefixes }}.',
      [MESSAGE_ID_UNEXPECTED_PREFIX]: '{{ kind }} name `{{ name }}` must not start with the boolish prefix `{{ prefix }}` because it is not boolean.',
    },
  },
  create: (context) => {
    const { parserServices } = <TSESLint.SourceCode><unknown>context.sourceCode;
    const services = <Maybe<ParserServicesWithTypeInformation>><unknown>parserServices;
    const program = services?.program;

    return buildVisitors(<const>{
      report: (nameNode, messageId, data) => {
        context.report({
          node: nameNode,
          messageId,
          data,
        });
      },
      services: program === undefined ? undefined : services,
      checker: program?.getTypeChecker(),
      returnClassifications: new Map(),
    } satisfies RuleContext);
  },
} satisfies RuleDefinition;
