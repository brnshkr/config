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

const isBlockComment = (
  comment: Maybe<TSESTree.Comment>,
// eslint-disable-next-line ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum
): comment is TSESTree.Comment => comment?.type === 'Block' && comment.value.startsWith('*');

export const extractBlockComment = (comments: TSESTree.Comment[]): Maybe<string> => {
  for (let index = comments.length - 1; index >= 0; index -= 1) {
    const comment = comments[index];

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

export const hasFileDescription = (text: Maybe<string>): boolean => text !== undefined
  && (hasDescription(text)
    || hasProseAfter(/@(?:file|fileoverview)\s+(?<prose>\S[^\n]*)/v, text));

export const getFileLevelBlockComment = (sourceCode: TSESLint.SourceCode): Maybe<string> => {
  for (const comment of sourceCode.getAllComments()) {
    if (!isBlockComment(comment)) {
      continue;
    }

    const text = `/*${comment.value}*/`;

    if (hasAnyTag(text, ['file', 'fileoverview'])) {
      return text;
    }
  }

  return undefined;
};

export const getEffectiveVisibilityTag = (
  symbolComment: Maybe<string>,
  fileComment: Maybe<string>,
): Maybe<VisibilityTag> => getVisibilityTag(symbolComment) ?? getVisibilityTag(fileComment);
