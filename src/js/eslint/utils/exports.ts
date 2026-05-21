import { isFunctionInitializer } from './ast';
import { extractBlockComment } from './jsdoc';

import type { TSESLint, TSESTree } from '@typescript-eslint/utils';
import type { Maybe } from '../../shared/types/core';

export type ExportedSymbolKind = 'Class'
  | 'Interface'
  | 'Type'
  | 'Enum'
  | 'Constant'
  | 'Function';

interface BaseSymbol<
  TKind extends ExportedSymbolKind,
  TDeclaration extends TSESTree.Node,
> {
  anchor: TSESTree.Node;
  declaration: TDeclaration;
  comment: Maybe<string>;
  kind: TKind;
  name: string;
}

type FunctionDeclarationNode = TSESTree.ArrowFunctionExpression
  | TSESTree.FunctionDeclaration
  | TSESTree.FunctionExpression
  | TSESTree.TSDeclareFunction
  | TSESTree.VariableDeclarator;

type ConstantDeclarationNode = TSESTree.CallExpression
  | TSESTree.Identifier
  | TSESTree.NewExpression
  | TSESTree.ObjectExpression
  | TSESTree.VariableDeclarator;

type NamedDeclarationWithOptionalId = TSESTree.ClassDeclaration
  | TSESTree.FunctionDeclaration
  | TSESTree.TSDeclareFunction
  | TSESTree.TSEnumDeclaration
  | TSESTree.TSInterfaceDeclaration
  | TSESTree.TSTypeAliasDeclaration;

export type ExportedSymbol = BaseSymbol<'Class', TSESTree.ClassDeclaration>
  | BaseSymbol<'Interface', TSESTree.TSInterfaceDeclaration>
  | BaseSymbol<'Type', TSESTree.TSTypeAliasDeclaration>
  | BaseSymbol<'Enum', TSESTree.TSEnumDeclaration>
  | BaseSymbol<'Function', FunctionDeclarationNode>
  | BaseSymbol<'Constant', ConstantDeclarationNode>;

const DEFAULT_NAME = 'default';
const ANONYMOUS_NAME = '<anonymous>';

/* eslint-disable ts/naming-convention -- Record keys mirror AST `Node.type` literal values */
const ATOMIC_NAMED_KIND_MAP: Partial<Record<TSESTree.Node['type'], ExportedSymbolKind>> = {
  ClassDeclaration: 'Class',
  FunctionDeclaration: 'Function',
  TSDeclareFunction: 'Function',
  TSEnumDeclaration: 'Enum',
  TSInterfaceDeclaration: 'Interface',
  TSTypeAliasDeclaration: 'Type',
};

const DEFAULT_KIND_MAP: Partial<Record<TSESTree.Node['type'], ExportedSymbolKind>> = {
  ArrowFunctionExpression: 'Function',
  CallExpression: 'Constant',
  FunctionExpression: 'Function',
  Identifier: 'Constant',
  NewExpression: 'Constant',
  ObjectExpression: 'Constant',
};
/* eslint-enable ts/naming-convention -- Restore rule */

const collectAtomicNamedDeclaration = (
  declaration: TSESTree.Node,
  anchor: TSESTree.Node,
  comment: Maybe<string>,
): Maybe<ExportedSymbol> => {
  const kind = ATOMIC_NAMED_KIND_MAP[declaration.type];

  if (kind === undefined) {
    return undefined;
  }

  const declarationCasted = <NamedDeclarationWithOptionalId>declaration;

  return <ExportedSymbol>{
    anchor,
    declaration,
    comment,
    kind,
    name: declarationCasted.id?.name ?? ANONYMOUS_NAME,
  };
};

/* eslint-disable ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum */
const collectVariableDeclaration = (
  declaration: TSESTree.VariableDeclaration,
  anchor: TSESTree.Node,
  comment: Maybe<string>,
): ExportedSymbol[] => declaration.declarations
  .filter((declarator) => declarator.id.type === 'Identifier')
  .map((declarator): ExportedSymbol => ({
    anchor,
    declaration: declarator,
    comment,
    kind: isFunctionInitializer(declarator.init) ? 'Function' : 'Constant',
    name: (<TSESTree.Identifier>declarator.id).name,
  }));

const collectNamedDeclaration = (
  declaration: TSESTree.Node,
  anchor: TSESTree.Node,
  comment: Maybe<string>,
): ExportedSymbol[] => {
  const atomic = collectAtomicNamedDeclaration(declaration, anchor, comment);

  if (atomic !== undefined) {
    return [atomic];
  }

  if (declaration.type === 'VariableDeclaration') {
    return collectVariableDeclaration(declaration, anchor, comment);
  }

  return [];
};

const collectDefaultDeclaration = (
  declaration: TSESTree.Node,
  anchor: TSESTree.Node,
  comment: Maybe<string>,
): ExportedSymbol[] => {
  if (declaration.type === 'ClassDeclaration' || declaration.type === 'FunctionDeclaration') {
    return collectNamedDeclaration(declaration, anchor, comment);
  }

  const kind = DEFAULT_KIND_MAP[declaration.type];

  if (kind === undefined) {
    return [];
  }

  const name = declaration.type === 'Identifier' ? declaration.name : DEFAULT_NAME;

  return [<ExportedSymbol>{
    anchor,
    declaration,
    comment,
    kind,
    name,
  }];
};
/* eslint-enable ts/no-unsafe-enum-comparison -- Restore rule */

export interface ExportVisitors {
  ExportDefaultDeclaration: (node: TSESTree.ExportDefaultDeclaration) => void;
  ExportNamedDeclaration: (node: TSESTree.ExportNamedDeclaration) => void;
}

export const buildExportVisitors = (
  sourceCode: TSESLint.SourceCode,
  onSymbol: (symbol: ExportedSymbol) => void,
): ExportVisitors => {
  const emit = (
    node: TSESTree.ExportDefaultDeclaration | TSESTree.ExportNamedDeclaration,
    collectDeclaration: typeof collectNamedDeclaration,
  ): void => {
    if (node.declaration === null) {
      return;
    }

    const comment = extractBlockComment(sourceCode.getCommentsBefore(node));

    for (const symbol of collectDeclaration(node.declaration, node, comment)) {
      onSymbol(symbol);
    }
  };

  return {
    ExportDefaultDeclaration: (node): void => {
      emit(node, collectDefaultDeclaration);
    },
    ExportNamedDeclaration: (node): void => {
      emit(node, collectNamedDeclaration);
    },
  };
};
