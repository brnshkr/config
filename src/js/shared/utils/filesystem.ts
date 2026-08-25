/**
 * @internal @brnshkr/config
 */

import fs from 'node:fs';
import path from 'node:path';

import type { Maybe } from '../types/core';

export const toPosix = (value: string): string => value.replaceAll('\\', '/');

export const doesFileExist = (filePath: string): boolean => {
  try {
    // eslint-disable-next-line node/no-sync -- Synchronous resolution mirrors ESLint's lifecycle
    fs.accessSync(filePath, fs.constants.R_OK);

    return true;
  } catch {
    return false;
  }
};

export const getMtime = (filePath: string): Maybe<number> => {
  try {
    // eslint-disable-next-line node/no-sync -- Synchronous stat mirrors ESLint's lifecycle
    return fs.statSync(filePath).mtimeMs;
  } catch {
    return undefined;
  }
};

export const readTextFile = (filePath: string): Maybe<string> => {
  try {
    // eslint-disable-next-line node/no-sync -- Synchronous read mirrors ESLint's lifecycle
    return fs.readFileSync(filePath, 'utf-8');
  } catch {
    return undefined;
  }
};

export const readJsonFile = (filePath: string): Maybe<unknown> => {
  const content = readTextFile(filePath);

  if (content === undefined) {
    return undefined;
  }

  try {
    return JSON.parse(content);
  } catch {
    return undefined;
  }
};

export const findNearestPackageJson = (startDirectory: string): Maybe<string> => {
  let current = path.resolve(startDirectory);
  let parent = path.dirname(current);

  while (current !== parent) {
    const candidate = path.join(current, 'package.json');

    if (doesFileExist(candidate)) {
      return candidate;
    }

    current = parent;
    parent = path.dirname(current);
  }

  return undefined;
};
