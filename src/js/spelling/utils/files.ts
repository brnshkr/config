/**
 * @internal @brnshkr/config/spelling
 */

import { execFileSync } from 'node:child_process';
import path from 'node:path';

import { doesFileExist } from '../../shared/utils/filesystem';

import type { SpellingSettings } from '../types/options';

const COMMAND_TIMEOUT_MILLISECONDS = 60_000;

const listTrackedFiles = (rootDirectory: string): string[] => {
  // eslint-disable-next-line node/no-sync -- Synchronous listing keeps both scanners on one surface
  const trackedFiles = execFileSync('git', ['ls-files', '-z'], {
    cwd: rootDirectory,
    encoding: 'utf-8',
    maxBuffer: Infinity,
    timeout: COMMAND_TIMEOUT_MILLISECONDS,
  });

  return trackedFiles.split('\0').filter((filePath) => filePath !== '');
};

const isScannedPath = (
  filePath: string,
  scannedExtensions: Set<string>,
  scannedNames: Set<string>,
  ignoreExpressions: RegExp[],
): boolean => {
  if (ignoreExpressions.some((ignoreExpression) => ignoreExpression.test(filePath))) {
    return false;
  }

  return scannedNames.has(path.basename(filePath)) || scannedExtensions.has(path.extname(filePath));
};

export const collectFilePaths = (rootDirectory: string, settings: SpellingSettings): string[] => {
  const scannedExtensions = new Set(settings.fileExtensions);
  const scannedNames = new Set(settings.fileNames);
  const ignoreExpressions = settings.ignorePatterns.map((ignorePattern) => new RegExp(ignorePattern, 'u'));

  return listTrackedFiles(rootDirectory)
    .filter((filePath) => isScannedPath(
      filePath,
      scannedExtensions,
      scannedNames,
      ignoreExpressions,
    ))
    .filter((filePath) => doesFileExist(path.join(rootDirectory, filePath)));
};
