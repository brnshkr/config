import fs from 'node:fs';
import path from 'node:path';

import type { Dirent } from 'node:fs';

const collator = new Intl.Collator(undefined, {
  numeric: true,
  sensitivity: 'base',
});

const naturalSort = (
  fileDescriptor1: Dirent,
  fileDescriptor2: Dirent,
): number => collator.compare(fileDescriptor1.name, fileDescriptor2.name);

// eslint-disable-next-line security/detect-non-literal-fs-filename -- Tests read their own fixture paths
export const readText = (filePath: string): string => fs.readFileSync(filePath, 'utf-8');

export const writeText = (filePath: string, content: string): void => {
  // eslint-disable-next-line security/detect-non-literal-fs-filename -- Tests write below their own temporary directory
  fs.writeFileSync(filePath, content);
};

export const makeDirectory = (directory: string): void => {
  // eslint-disable-next-line security/detect-non-literal-fs-filename -- Tests write below their own temporary directory
  fs.mkdirSync(directory);
};

export const setModificationTime = (filePath: string, secondsSinceEpoch: number): void => {
  // eslint-disable-next-line security/detect-non-literal-fs-filename -- Tests write below their own temporary directory
  fs.utimesSync(filePath, secondsSinceEpoch, secondsSinceEpoch);
};

export const traverseDirectory = (directory: string, onEncounterFile: (filePath: string) => void): void => {
  // eslint-disable-next-line security/detect-non-literal-fs-filename -- Tests traverse their own fixture directories
  const fileDescriptors = fs.readdirSync(directory, { withFileTypes: true }).toSorted(naturalSort);

  for (const fileDescriptor of fileDescriptors) {
    const fullPath = path.join(directory, fileDescriptor.name);

    if (fileDescriptor.isDirectory()) {
      traverseDirectory(fullPath, onEncounterFile);
    } else {
      onEncounterFile(fullPath);
    }
  }
};
