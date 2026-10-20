/**
 * @internal @brnshkr/config
 */

import { packageOrganization } from '#shared/utils/package-json.ts';

import type { MarkdownLanguage } from '@eslint/markdown';
import type { Maybe } from '#shared/types/core.ts';

type MarkdownRoot = Parameters<MarkdownLanguage['createSourceCode']>[1]['ast'];
type MarkdownNode = MarkdownRoot | MarkdownRoot['children'][number];

export interface FencedCode {
  code: string;
  language: string;
  firstLineIndex: number;
  lineCount: number;
  linePrefix: string;
}

export const SKIP_MARKER = `<!-- ${packageOrganization}-skip -->`;

const getLinePrefix = (lines: readonly string[], firstLineIndex: number, codeLines: readonly string[]): string => {
  const sampleIndex = codeLines.findIndex((codeLine) => codeLine !== '');
  const sourceLine = lines.at(firstLineIndex + sampleIndex) ?? '';

  return sampleIndex === -1 ? '' : sourceLine.slice(0, sourceLine.length - (codeLines.at(sampleIndex) ?? '').length);
};

const isSkipMarker = (node: Maybe<MarkdownNode>): boolean => node?.type === 'html'
  && node.value.trim() === SKIP_MARKER;

const collectFencedCodes = (node: MarkdownNode, lines: readonly string[], fencedCodes: FencedCode[]): void => {
  if (!('children' in node)) {
    return;
  }

  for (const [index, child] of node.children.entries()) {
    const { position } = child;
    const isFencedCode = child.type === 'code' && position !== undefined && !isSkipMarker(node.children[index - 1]);
    const language = isFencedCode ? (child.lang ?? '').toLowerCase() : '';

    if (isFencedCode && language !== '') {
      const codeLines = child.value.split('\n');

      fencedCodes.push({
        code: child.value,
        language,
        firstLineIndex: position.start.line,
        lineCount: codeLines.length,
        linePrefix: getLinePrefix(lines, position.start.line, codeLines),
      });
    }

    collectFencedCodes(child, lines, fencedCodes);
  }
};

export const findMarkdownFencedCodes = (markdownLanguage: MarkdownLanguage, markdown: string): FencedCode[] => {
  const parseResult = markdownLanguage.parse({
    body: markdown,
    path: '',
    physicalPath: '',
    bom: false,
  }, {
    languageOptions: {},
  });

  const fencedCodes: FencedCode[] = [];

  if (parseResult.ok) {
    collectFencedCodes(parseResult.ast, markdown.split('\n'), fencedCodes);
  }

  return fencedCodes;
};

export const findDocblockFencedCodes = (markdownLanguage: MarkdownLanguage, source: string): FencedCode[] => {
  const fencedCodes: FencedCode[] = [];

  for (const docblock of source.matchAll(/\/\*\*[\s\S]*?\*\//gv)) {
    const firstLineIndex = source.slice(0, docblock.index).split('\n').length - 1;
    const docblockLines = docblock[0].split('\n');
    const decorations: string[] = [];
    const contents: string[] = [];

    for (const line of docblockLines) {
      const unindentedLine = line.replace(/^[\t ]*/v, '').replace(/^\/\*\*/v, '');
      const starlessLine = unindentedLine.replace(/^\*/v, '').replace(/^ /v, '');
      const content = unindentedLine.startsWith('*/') ? '' : starlessLine;

      decorations.push(line.slice(0, line.length - content.length));
      contents.push(content);
    }

    const docblockFencedCodes = findMarkdownFencedCodes(markdownLanguage, contents.join('\n'));

    for (const fencedCode of docblockFencedCodes) {
      fencedCodes.push({
        ...fencedCode,
        firstLineIndex: firstLineIndex + fencedCode.firstLineIndex,
        linePrefix: (decorations.at(fencedCode.firstLineIndex - 1) ?? '') + fencedCode.linePrefix,
      });
    }
  }

  return fencedCodes;
};

export const replaceFencedCode = (text: string, fencedCode: FencedCode, code: string): string => {
  const lines = text.split('\n');

  const prefixedCodeLines = code
    .split('\n')
    .map((codeLine) => (codeLine === '' ? fencedCode.linePrefix.trimEnd() : fencedCode.linePrefix + codeLine));

  lines.splice(fencedCode.firstLineIndex, fencedCode.lineCount, ...prefixedCodeLines);

  return lines.join('\n');
};
