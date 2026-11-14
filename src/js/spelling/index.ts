import path from 'node:path';

import { readTextFile } from '#shared/utils/filesystem.ts';
import { getConfigDirectory } from '#shared/utils/project-directory.ts';
import { collectFilePaths } from '#spelling/utils/files.ts';
import { findInFile } from '#spelling/utils/findings.ts';
import { buildPatterns } from '#spelling/utils/patterns.ts';
import { readAllowlist, readSettings } from '#spelling/utils/settings.ts';

import type { SpellingFinding, SpellingOptions } from '#spelling/types/options.ts';

const compareCaseInsensitively = (firstValue: string, secondValue: string): number => {
  const firstLowercased = firstValue.toLowerCase();
  const secondLowercased = secondValue.toLowerCase();

  if (firstLowercased !== secondLowercased) {
    return firstLowercased < secondLowercased
      ? -1
      : 1;
  }

  return firstValue < secondValue
    ? -1
    : Number(firstValue > secondValue);
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

/**
 * Report every British spelling in a repository's tracked prose, docblocks and identifiers.
 *
 * @api
 *
 * @param options - where to scan, which settings to read, and which files to look at
 *
 * @returns every finding, sorted by path, line, and word
 *
 * @throws {Error} when the shipped spelling defaults cannot be found
 * @throws {Error} when a settings file cannot be read
 * @throws {Error} when an ignore pattern cannot be checked for super-linear backtracking
 * @throws {Error} when an ignore pattern can backtrack super-linearly
 * @throws {Error} when an allowed word is already covered by a broader entry
 *
 * @example
 * ```js
 * scan({
 *   rootDirectory: process.cwd(),
 * });
 * ```
 */
export const scan = (options?: Partial<SpellingOptions>): SpellingFinding[] => {
  const {
    rootDirectory = process.cwd(),
    configPath = `${getConfigDirectory()}/spelling.json`,
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
