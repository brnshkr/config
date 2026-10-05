/**
 * @internal @brnshkr/config/eslint
 */

import { createPattern } from '#shared/utils/pattern.ts';

import type { TSESLint, TSESTree } from '@typescript-eslint/utils';
import type { Maybe } from '#shared/types/core.ts';

export const TAG_API = 'api';
export const TAG_INTERNAL = 'internal';
export type VisibilityTag = typeof TAG_API | typeof TAG_INTERNAL;

export interface JsdocTag {
  tag: string;
  name: string;
  description: string;
}

const NAMED_TAGS = new Set([
  'param',
  'property',
  'template',
]);

const createTagLinePattern = (): RegExp => /^@(?<tag>[^\s\/]+)(?=\s|$)/v;

const findClosingIndex = (text: string, brackets: RegExp, opening: string): Maybe<number> => {
  let depth = 0;

  for (const bracket of text.matchAll(brackets)) {
    depth += bracket[0] === opening ? 1 : -1;

    if (depth === 0) {
      return bracket.index;
    }
  }

  return undefined;
};

const skipType = (text: string): Maybe<string> => {
  if (!text.startsWith('{')) {
    return text;
  }

  const closingIndex = findClosingIndex(text, /[\{\}]/gv, '{');

  return closingIndex === undefined ? undefined : text.slice(closingIndex + 1);
};

const readName = (text: string, tag: string): string => {
  if (text.startsWith('[')) {
    return text.slice(0, (findClosingIndex(text, /[\[\]]/gv, '[') ?? -1) + 1);
  }

  return /^"[^"]*"/v.exec(text)?.[0]
    ?? (tag === 'template' ? /^[^\s,]+(?:\s*,\s*[^\s,]+)*/v : /^\S+/v).exec(text)?.[0]
    ?? '';
};

const splitTag = (tag: string, body: string): JsdocTag => {
  const textAfterType = skipType(body.trimStart())?.trimStart() ?? '';
  const name = NAMED_TAGS.has(tag) ? readName(textAfterType, tag) : '';

  return {
    tag,
    name,
    description: textAfterType.slice(name.length).trim(),
  };
};

export const parseJsdocTags = (comment: string): JsdocTag[] => {
  const tagBodies: {
    tag: string;
    lines: string[];
  }[] = [];

  let isFenced = false;

  for (const line of comment.replaceAll(/^\/\*\*|\*\/$/gv, '').split('\n')) {
    const content = line.replace(/^\s*\*?\s?/v, '');
    const tagName = isFenced ? undefined : createTagLinePattern().exec(content)?.groups?.['tag'];

    if (tagName === undefined) {
      tagBodies.at(-1)?.lines.push(content);
    } else {
      tagBodies.push({
        tag: tagName,
        lines: [content.slice(tagName.length + 1)],
      });
    }

    isFenced = content.matchAll(/```/gv).reduce((wasFenced) => !wasFenced, isFenced);
  }

  return tagBodies.map(({ tag, lines }) => splitTag(tag, lines.join('\n')));
};

const hasProse = (description: string): boolean => /[A-Za-z]/v.test(description.replace(/^-\s*/v, ''));
const normalizeName = (name: string): string => name.replaceAll(/^["\[]|["\]]$/gv, '').split('=', 1)[0]?.trim() ?? '';

export const isBlockComment = (
  comment: Maybe<TSESTree.Comment>,
// eslint-disable-next-line ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum
): comment is TSESTree.BlockComment => comment?.type === 'Block' && comment.value.startsWith('*');

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
  && createPattern('v')`@${tag}\b`.test(comment);

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

export const hasParameterProse = (comment: string, parameterName: string): boolean => parseJsdocTags(comment).some(
  ({ tag, name, description }) => tag === 'param' && normalizeName(name) === parameterName && hasProse(description),
);

export const hasReturnsWithProse = (comment: string): boolean => parseJsdocTags(comment).some(
  ({ tag, description }) => (tag === 'returns' || tag === 'return') && hasProse(description),
);

export const getUndescribedTags = (comment: string, tagNames: readonly string[]): JsdocTag[] => parseJsdocTags(comment)
  .filter(({ tag, description }) => tagNames.includes(tag) && !hasProse(description));

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

const hasModuleSource = (node: TSESTree.Node): boolean => 'source' in node && (node.source ?? undefined) !== undefined;

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
