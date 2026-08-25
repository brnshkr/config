/**
 * @internal @brnshkr/config/eslint
 */

import { isBlockComment } from '../../utils/jsdoc';

import type { ParserServicesWithTypeInformation, TSESLint, TSESTree } from '@typescript-eslint/utils';
import type ts from 'typescript';
import type { Maybe } from '../../../shared/types/core';
import type { RuleDefinition } from '.';

export const MESSAGE_ID_UNRESOLVED_REFERENCE = 'unresolvedReference';

interface DocNameNode extends ts.Node {
  left?: DocNameNode;
  right?: DocNameNode;
}

interface DocCommentPart {
  name?: DocNameNode;
  text?: string;
}

interface DocTag {
  name?: {
    name?: DocNameNode;
  };
}

interface DocComment {
  comment?: unknown;
  tags?: DocTag[];
}

interface NodeWithDocComments {
  jsDoc?: DocComment[];
}

interface DocNameReference {
  nameNode: DocNameNode;
  isInlineTag: boolean;
  trailingText: string;
}

interface ReferenceFinding {
  target: string;
  loc: TSESTree.Position;
}

interface LexicalReference extends ReferenceFinding {
  headIdentifier: string;
}

const createReferencePattern = (): RegExp => /(?:\{@|\*\s+@)(?:link(?:code|plain)?|see)\s+(?<target>[^\s\}]+)/gv;
const startsUpperCase = (value: string): boolean => /\p{Uppercase_Letter}/v.test(value.slice(0, 1));

const isCheckableTarget = (target: string): boolean => !target.includes('/')
  && !target.includes('~')
  && !target.includes(':');

const startsContinuation = (trailingText: string): boolean => trailingText.startsWith('(')
  || trailingText.startsWith(':');

const getHeadIdentifier = (target: string): string => target.split(/[#.]/v)[0] ?? '';

const getLeftmostNameNode = (nameNode: DocNameNode): DocNameNode => {
  let leftmost = nameNode;

  while (leftmost.left !== undefined) {
    leftmost = leftmost.left;
  }

  return leftmost;
};

const collectInlineReferences = (docComment: DocComment): DocNameReference[] => (
  Array.isArray(docComment.comment) ? <DocCommentPart[]>docComment.comment : []
)
  .filter((part) => part.name !== undefined)
  .map((part) => (<DocNameReference>{
    nameNode: part.name,
    isInlineTag: true,
    trailingText: part.text ?? '',
  }));

const collectBlockReferences = (docComment: DocComment): DocNameReference[] => (docComment.tags ?? [])
  .filter((tag) => tag.name?.name !== undefined)
  .map((tag) => (<DocNameReference>{
    nameNode: tag.name?.name,
    isInlineTag: false,
    trailingText: '',
  }));

const collectNameReferences = (node: NodeWithDocComments): DocNameReference[] => (node.jsDoc ?? [])
  .flatMap((docComment) => [...collectInlineReferences(docComment), ...collectBlockReferences(docComment)]);

const resolveNameNodeType = (checker: ts.TypeChecker, nameNode: DocNameNode): Maybe<ts.Type> => {
  if (nameNode.left === undefined || nameNode.right === undefined) {
    const symbol = checker.getSymbolAtLocation(nameNode);

    return symbol === undefined ? undefined : checker.getDeclaredTypeOfSymbol(symbol);
  }

  const property = resolveNameNodeType(checker, nameNode.left)?.getProperty(nameNode.right.getText());

  return property === undefined ? undefined : checker.getTypeOfSymbolAtLocation(property, nameNode);
};

const isMemberNameResolved = (
  checker: ts.TypeChecker,
  nameNode: DocNameNode,
): boolean => resolveNameNodeType(checker, nameNode) !== undefined;

const collectDeclaredNames = (sourceCode: TSESLint.SourceCode): Set<string> => {
  const declaredNames = new Set<string>();

  for (const scope of sourceCode.scopeManager?.scopes ?? []) {
    for (const variable of scope.variables) {
      declaredNames.add(variable.name);
    }
  }

  return declaredNames;
};

const collectLexicalReferences = (comment: TSESTree.Comment): LexicalReference[] => {
  const references: LexicalReference[] = [];

  for (const [offset, line] of `/*${comment.value}*/`.split('\n').entries()) {
    for (const match of line.matchAll(createReferencePattern())) {
      const rawTarget = match.groups?.['target'] ?? '';
      const target = rawTarget.split('|')[0] ?? '';
      const targetColumn = match.index + (match[0].length - rawTarget.length);

      references.push({
        target,
        headIdentifier: getHeadIdentifier(target),
        loc: {
          line: comment.loc.start.line + offset,
          column: offset === 0 ? comment.loc.start.column + targetColumn : targetColumn,
        },
      });
    }
  }

  return references;
};

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/resolvable-doc-reference.md
 */
export const resolvableDocReferenceRule = <const>{
  meta: {
    type: 'suggestion',
    docs: {
      description: 'Require every `@see` and `@link` target in a JSDoc comment to name a symbol that exists.',
      url: 'https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/resolvable-doc-reference.md',
    },
    messages: {
      [MESSAGE_ID_UNRESOLVED_REFERENCE]: 'Reference `{{ name }}` does not exist.',
    },
  },
  create: (context) => {
    const sourceCode = <TSESLint.SourceCode><unknown>context.sourceCode;
    const services = <Maybe<ParserServicesWithTypeInformation>><unknown>sourceCode.parserServices;
    const program = <Maybe<ts.Program>>services?.program;
    const typeChecker = program?.getTypeChecker();
    const findings = new Map<string, ReferenceFinding>();

    const addFinding = (target: string, loc: TSESTree.Position): void => {
      const key = `${String(loc.line)}:${String(loc.column)}:${target}`;

      findings.set(key, findings.get(key) ?? { target, loc });
    };

    const checkNameNode = (checker: ts.TypeChecker, reference: DocNameReference): void => {
      const { isInlineTag, nameNode, trailingText } = reference;
      const name = nameNode.getText();
      const target = startsContinuation(trailingText) ? `${name}${trailingText}` : name;

      if (!isCheckableTarget(target) || checker.getSymbolAtLocation(nameNode) !== undefined) {
        return;
      }

      if (nameNode.right !== undefined && isMemberNameResolved(checker, nameNode)) {
        return;
      }

      const isHeadResolved = checker.getSymbolAtLocation(getLeftmostNameNode(nameNode)) !== undefined;

      if (!isInlineTag && !isHeadResolved && !startsUpperCase(target)) {
        return;
      }

      const position = nameNode.getSourceFile().getLineAndCharacterOfPosition(nameNode.getStart());

      addFinding(target, {
        line: position.line + 1,
        column: position.character,
      });
    };

    return {
      '*': (node: TSESTree.Node): void => {
        if (services === undefined || typeChecker === undefined) {
          return;
        }

        const tsNode = <Maybe<NodeWithDocComments>>services.esTreeNodeToTSNodeMap.get(node);

        for (const reference of tsNode === undefined ? [] : collectNameReferences(tsNode)) {
          checkNameNode(typeChecker, reference);
        }
      },
      'Program:exit': (): void => {
        const declaredNames = collectDeclaredNames(sourceCode);

        for (const comment of sourceCode.getAllComments()) {
          if (!isBlockComment(comment)) {
            continue;
          }

          for (const reference of collectLexicalReferences(comment)) {
            if (isCheckableTarget(reference.target)
              && startsUpperCase(reference.headIdentifier)
              && !declaredNames.has(reference.headIdentifier)) {
              addFinding(reference.target, reference.loc);
            }
          }
        }

        for (const finding of findings.values()) {
          context.report({
            loc: finding.loc,
            messageId: MESSAGE_ID_UNRESOLVED_REFERENCE,
            data: {
              name: finding.target,
            },
          });
        }
      },
    };
  },
} satisfies RuleDefinition;
