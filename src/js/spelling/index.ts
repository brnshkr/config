import path from 'node:path';

import { getEnvironmentValue } from '#shared/utils/environment.ts';
import { readTextFile } from '#shared/utils/filesystem.ts';
import { readOwnValue } from '#shared/utils/object.ts';
import { createPattern } from '#shared/utils/pattern.ts';
import { collectFilePaths } from '#spelling/utils/files.ts';
import { buildPatterns } from '#spelling/utils/patterns.ts';
import { readAllowlist, readSettings } from '#spelling/utils/settings.ts';

import type {
  AllowedLiteral,
  Allowlist,
  SpellingFinding,
  SpellingOptions,
  SpellingPattern,
} from '#spelling/types/options.ts';

const EVERY_PATH = '*';
const DEFAULT_CONFIG_DIRECTORY = 'conf';

const maskAllowedLiterals = (
  line: string,
  allowedLiterals: AllowedLiteral[],
  lineNumber: number,
): string => {
  let maskedLine = line;

  for (const { text, lineNumbers } of allowedLiterals) {
    if (lineNumbers === undefined || lineNumbers.includes(lineNumber)) {
      maskedLine = maskedLine.replaceAll(
        createPattern('giu')`${text}`,
        (matchedText) => '.'.repeat(matchedText.length),
      );
    }
  }

  return maskedLine;
};

const compareCaseInsensitively = (firstValue: string, secondValue: string): number => {
  const firstLowercased = firstValue.toLowerCase();
  const secondLowercased = secondValue.toLowerCase();

  if (firstLowercased !== secondLowercased) {
    return firstLowercased < secondLowercased ? -1 : 1;
  }

  return firstValue < secondValue ? -1 : Number(firstValue > secondValue);
};

const compareFindings = (firstFinding: SpellingFinding, secondFinding: SpellingFinding): number => {
  const pathOrder = compareCaseInsensitively(firstFinding.path, secondFinding.path);

  if (pathOrder !== 0) {
    return pathOrder;
  }

  return firstFinding.line === secondFinding.line
    ? compareCaseInsensitively(firstFinding.word, secondFinding.word)
    : firstFinding.line - secondFinding.line;
};

const findInFile = (
  fileContents: string,
  filePath: string,
  patterns: SpellingPattern[],
  allowlist: Allowlist,
): SpellingFinding[] => {
  const allowedLiterals = [...readOwnValue(allowlist, EVERY_PATH) ?? [], ...readOwnValue(allowlist, filePath) ?? []];
  const findings: SpellingFinding[] = [];

  for (const [index, line] of fileContents.split('\n').entries()) {
    const lineNumber = index + 1;
    const maskedLine = maskAllowedLiterals(line, allowedLiterals, lineNumber);

    for (const { pattern, toAmericanSpelling } of patterns) {
      for (const [word] of maskedLine.matchAll(pattern)) {
        findings.push({
          path: filePath,
          line: lineNumber,
          word,
          suggestion: toAmericanSpelling(word),
        });
      }
    }
  }

  return findings;
};

/**
 * Report every British spelling in a repository's tracked prose, docblocks and identifiers.
 *
 * @api
 *
 * @param options - where to scan, which settings to read, and which files to look at
 *
 * @returns every finding, sorted by path, line and word
 *
 * @example
 * ```js
 * scan({ rootDirectory: process.cwd() });
 * ```
 */
export const scan = (options?: Partial<SpellingOptions>): SpellingFinding[] => {
  const configDirectory = getEnvironmentValue('BRNSHKR_CONFIG_DIR') ?? '';

  const {
    rootDirectory = process.cwd(),
    configPath = `${configDirectory === '' ? DEFAULT_CONFIG_DIRECTORY : configDirectory}/spelling.json`,
    paths,
  } = options ?? {};

  const settings = readSettings(rootDirectory, configPath);
  const allowlist = readAllowlist(settings);
  const patterns = buildPatterns(settings);
  const filePaths = paths ?? collectFilePaths(rootDirectory, settings);

  const findingsPerFile = filePaths.map((filePath) => findInFile(
    readTextFile(path.join(rootDirectory, filePath)) ?? '',
    filePath,
    patterns,
    allowlist,
  ));

  return findingsPerFile.flat().toSorted(compareFindings);
};
