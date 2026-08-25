import type { TSESLint, TSESTree } from '@typescript-eslint/utils';
import type { Maybe } from '../../shared/types/core';

export const TAG_API = 'api';
export const TAG_INTERNAL = 'internal';
export type VisibilityTag = typeof TAG_API | typeof TAG_INTERNAL;

const escapeRegExp = (value: string): string => value.replaceAll(/[$\(\)*+.?\[\\\]^\{\|\}]/gv, String.raw`\$&`);

const hasProseAfter = (pattern: RegExp, comment: string): boolean => {
  const prose = pattern.exec(comment)?.groups?.['prose'];

  return typeof prose === 'string' && /[A-Za-z]/v.test(prose);
};

export const isBlockComment = (
  comment: Maybe<TSESTree.Comment>,
// eslint-disable-next-line ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum
): comment is TSESTree.Comment => comment?.type === 'Block' && comment.value.startsWith('*');

export const extractBlockComment = (comments: TSESTree.Comment[], node: TSESTree.Node): Maybe<string> => {
  let expectedEndLine = node.loc.start.line - 1;

  for (const comment of comments.toReversed()) {
    if (comment.loc.end.line !== expectedEndLine) {
      return undefined;
    }

    expectedEndLine = comment.loc.start.line - 1;

    if (isBlockComment(comment)) {
      return `/*${comment.value}*/`;
    }
  }

  return undefined;
};

export const hasTag = (
  comment: Maybe<string>,
  tag: string,
): boolean => comment !== undefined
  && new RegExp(String.raw`@${tag}\b`, 'v').test(comment);

export const hasAnyTag = (
  comment: Maybe<string>,
  tags: readonly string[],
): boolean => tags.some((tag) => hasTag(comment, tag));

export const hasDescription = (comment: Maybe<string>): boolean => {
  if (comment === undefined) {
    return false;
  }

  const beforeTags = /\/\*\*(?<body>.*?)(?:\n[\t ]*\*[\t ]+@|\*\/)/sv.exec(comment)?.groups?.['body'] ?? '';

  return /^[\t ]*\*[\t ]+[[^\s*\/]--@][^\n]*/mv.test(beforeTags);
};

export const hasParameterProse = (comment: string, parameterName: string): boolean => hasProseAfter(
  new RegExp(String.raw`@param\b[^\n]*?\b${escapeRegExp(parameterName)}\b(?<prose>[^\n]*)`, 'v'),
  comment,
);

export const hasReturnsWithProse = (comment: string): boolean => hasProseAfter(
  /@returns?\s+\S+\s+(?<prose>\S[^\n]*)/v,
  comment,
);

export const getVisibilityTag = (comment: Maybe<string>): Maybe<VisibilityTag> => {
  if (hasTag(comment, TAG_INTERNAL)) {
    return TAG_INTERNAL;
  }

  if (hasTag(comment, TAG_API)) {
    return TAG_API;
  }

  return undefined;
};

export const hasConflictingVisibilityTags = (comment: Maybe<string>): boolean => hasTag(comment, TAG_API)
  && hasTag(comment, TAG_INTERNAL);

const hasModuleSource = (node: TSESTree.Node): boolean => 'source' in node && node.source !== null;

export const findFileLevelComment = (sourceCode: TSESLint.SourceCode): Maybe<TSESTree.Comment> => {
  const [firstStatement] = sourceCode.ast.body;

  if (firstStatement === undefined) {
    return undefined;
  }

  const leadingComments = sourceCode.getCommentsBefore(firstStatement).filter(isBlockComment);
  const [fileComment] = leadingComments;

  if (fileComment === undefined) {
    return undefined;
  }

  if (leadingComments.length > 1 || hasModuleSource(firstStatement)) {
    return fileComment;
  }

  return firstStatement.loc.start.line - fileComment.loc.end.line > 1 ? fileComment : undefined;
};

export const getFileLevelBlockComment = (sourceCode: TSESLint.SourceCode): Maybe<string> => {
  const comment = findFileLevelComment(sourceCode);

  return comment === undefined ? undefined : `/*${comment.value}*/`;
};

export const getEffectiveVisibilityTag = (
  symbolComment: Maybe<string>,
  fileComment: Maybe<string>,
): Maybe<VisibilityTag> => getVisibilityTag(symbolComment) ?? getVisibilityTag(fileComment);
