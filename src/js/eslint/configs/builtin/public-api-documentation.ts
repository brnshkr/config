import {
  getNamedKeyText,
  getParameterName,
  isAbstractMethod,
  isAccessibleMethod,
  isClassMethodLike,
  isFluentReturn,
  isVoidLikeReturn,
  resolveFunctionShape,
} from '../../utils/ast';

import { buildExportVisitors } from '../../utils/exports';

import {
  extractBlockComment,
  getEffectiveVisibilityTag,
  getFileLevelBlockComment,
  hasDescription,
  hasFileDescription,
  hasParameterProse,
  hasReturnsWithProse,
  hasTag,
  TAG_API,
  TAG_INTERNAL,
} from '../../utils/jsdoc';

import { isPublicApiFile } from '../../utils/public-api';

import type { TSESLint, TSESTree } from '@typescript-eslint/utils';
import type { Maybe } from '../../../shared/types/core';
import type { ExportedSymbol } from '../../utils/exports';
import type { PackageExportsResolverOptions } from '../../utils/package-exports';
import type { RuleDefinition } from '.';

export const MESSAGE_ID_MISSING_FILE_DESCRIPTION = 'missingFileDescription';
export const MESSAGE_ID_MISSING_DESCRIPTION = 'missingDescription';
export const MESSAGE_ID_MISSING_PARAM = 'missingParam';
export const MESSAGE_ID_MISSING_RETURNS = 'missingReturns';
export const MESSAGE_ID_MISSING_EXAMPLE = 'missingExample';

const KIND_METHOD = 'Method';
const KIND_CONSTRUCTOR = 'Constructor';

interface FunctionLike {
  anchor: TSESTree.Node;
  kind: string;
  name: string;
  comment: Maybe<string>;
  parameters: TSESTree.Parameter[];
  returnType: Maybe<TSESTree.TSTypeAnnotation>;
  isAbstract?: boolean;
}

interface RuleContext {
  context: TSESLint.RuleContext<string, unknown[]>;
  sourceCode: TSESLint.SourceCode;
  fileComment: Maybe<string>;
}

const reportMissingDescription = (
  ruleContext: RuleContext,
  anchor: TSESTree.Node,
  kind: string,
  name: string,
): void => {
  ruleContext.context.report({
    node: anchor,
    messageId: MESSAGE_ID_MISSING_DESCRIPTION,
    data: {
      kind,
      name,
    },
  });
};

const checkFunctionParameters = (ruleContext: RuleContext, functionLike: FunctionLike, text: string): void => {
  for (const parameter of functionLike.parameters) {
    const parameterName = getParameterName(parameter);

    if (parameterName !== undefined && !hasParameterProse(text, parameterName)) {
      ruleContext.context.report({
        node: functionLike.anchor,
        messageId: MESSAGE_ID_MISSING_PARAM,
        data: {
          kind: functionLike.kind,
          name: functionLike.name,
          parameterName,
        },
      });
    }
  }
};

const checkFunctionReturns = (
  ruleContext: RuleContext,
  functionLike: FunctionLike,
  text: string,
  isFluent: boolean,
): void => {
  if (isFluent || isVoidLikeReturn(functionLike.returnType) || hasReturnsWithProse(text)) {
    return;
  }

  ruleContext.context.report({
    node: functionLike.anchor,
    messageId: MESSAGE_ID_MISSING_RETURNS,
    data: {
      kind: functionLike.kind,
      name: functionLike.name,
    },
  });
};

const checkFunctionExample = (
  ruleContext: RuleContext,
  functionLike: FunctionLike,
  text: string,
  isFluent: boolean,
): void => {
  if (functionLike.parameters.length === 0 || isFluent || functionLike.isAbstract === true || hasTag(text, 'example')) {
    return;
  }

  ruleContext.context.report({
    node: functionLike.anchor,
    messageId: MESSAGE_ID_MISSING_EXAMPLE,
    data: {
      kind: functionLike.kind,
      name: functionLike.name,
    },
  });
};

const checkFunctionLike = (ruleContext: RuleContext, functionLike: FunctionLike): void => {
  const text = functionLike.comment ?? '';

  if (functionLike.kind !== KIND_CONSTRUCTOR && !hasDescription(functionLike.comment)) {
    reportMissingDescription(
      ruleContext,
      functionLike.anchor,
      functionLike.kind,
      functionLike.name,
    );
  }

  checkFunctionParameters(ruleContext, functionLike, text);

  const isFluent = isFluentReturn(functionLike.returnType);

  checkFunctionReturns(
    ruleContext,
    functionLike,
    text,
    isFluent,
  );

  checkFunctionExample(
    ruleContext,
    functionLike,
    text,
    isFluent,
  );
};

const checkClassMembers = (
  ruleContext: RuleContext,
  node: TSESTree.ClassDeclaration,
): void => {
  for (const member of node.body.body) {
    if (!isClassMethodLike(member) || !isAccessibleMethod(member)) {
      continue;
    }

    const methodComment = extractBlockComment(ruleContext.sourceCode.getCommentsBefore(member));

    if (getEffectiveVisibilityTag(methodComment, ruleContext.fileComment) === TAG_INTERNAL) {
      continue;
    }

    checkFunctionLike(ruleContext, {
      anchor: member,
      kind: member.kind === 'constructor' ? KIND_CONSTRUCTOR : KIND_METHOD,
      name: getNamedKeyText(member.key),
      comment: methodComment,
      parameters: member.value.params,
      returnType: member.value.returnType,
      isAbstract: isAbstractMethod(member),
    });
  }
};

const checkInterfaceMembers = (
  ruleContext: RuleContext,
  node: TSESTree.TSInterfaceDeclaration,
): void => {
  for (const member of node.body.body) {
    // eslint-disable-next-line ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum
    if (member.type !== 'TSMethodSignature') {
      continue;
    }

    const methodComment = extractBlockComment(ruleContext.sourceCode.getCommentsBefore(member));

    if (getEffectiveVisibilityTag(methodComment, ruleContext.fileComment) === TAG_INTERNAL) {
      continue;
    }

    checkFunctionLike(ruleContext, {
      anchor: member,
      kind: KIND_METHOD,
      name: getNamedKeyText(member.key),
      comment: methodComment,
      parameters: member.params,
      returnType: member.returnType,
      isAbstract: true,
    });
  }
};

const checkSymbol = (ruleContext: RuleContext, symbol: ExportedSymbol): void => {
  if (symbol.kind === 'Function') {
    const shape = resolveFunctionShape(symbol.declaration);

    if (shape === undefined) {
      return;
    }

    checkFunctionLike(ruleContext, {
      anchor: symbol.anchor,
      kind: symbol.kind,
      name: symbol.name,
      comment: symbol.comment,
      parameters: shape.parameters,
      returnType: shape.returnType,
    });

    return;
  }

  if (!hasDescription(symbol.comment)) {
    reportMissingDescription(
      ruleContext,
      symbol.anchor,
      symbol.kind,
      symbol.name,
    );
  }

  if (symbol.kind === 'Class') {
    checkClassMembers(ruleContext, symbol.declaration);
  } else if (symbol.kind === 'Interface') {
    checkInterfaceMembers(ruleContext, symbol.declaration);
  }
};

export const publicApiDocumentationRule = <const>{
  meta: {
    type: 'suggestion',
    docs: {
      description: 'Hold every `@api`-tagged symbol in a public-API source file to a consistent JSDoc standard.',
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
      [MESSAGE_ID_MISSING_FILE_DESCRIPTION]: 'Public-API source file must carry a top-of-file `@file` JSDoc block with a description.',
      [MESSAGE_ID_MISSING_DESCRIPTION]: '{{ kind }} `{{ name }}` is `@api` and must carry a description before the first JSDoc tag.',
      [MESSAGE_ID_MISSING_PARAM]: '{{ kind }} `{{ name }}` is `@api`; parameter `{{ parameterName }}` must have a `@param` tag with a description.',
      [MESSAGE_ID_MISSING_RETURNS]: '{{ kind }} `{{ name }}` is `@api` and returns a non-void type; a `@returns` tag with a description is required.',
      [MESSAGE_ID_MISSING_EXAMPLE]: '{{ kind }} `{{ name }}` is `@api` and accepts parameters; an `@example` tag is required.',
    },
  },
  create: (context) => {
    const options = <PackageExportsResolverOptions>(context.options[0] ?? {});

    if (!isPublicApiFile(options, context.cwd, context.filename)) {
      return {};
    }

    const sourceCode = <TSESLint.SourceCode><unknown>context.sourceCode;
    const fileComment = getFileLevelBlockComment(sourceCode);

    if (!hasFileDescription(fileComment)) {
      context.report({
        messageId: MESSAGE_ID_MISSING_FILE_DESCRIPTION,
        loc: {
          line: 1,
          column: 0,
        },
      });
    }

    const ruleContext = <const>{
      context: <TSESLint.RuleContext<string, unknown[]>><unknown>context,
      sourceCode,
      fileComment,
    } satisfies RuleContext;

    return buildExportVisitors(sourceCode, (symbol) => {
      if (getEffectiveVisibilityTag(symbol.comment, fileComment) !== TAG_API) {
        return;
      }

      checkSymbol(ruleContext, symbol);
    });
  },
} satisfies RuleDefinition;
