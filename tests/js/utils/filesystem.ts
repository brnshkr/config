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

export const traverseDirectory = (directory: string, onEncounterFile: (filePath: string) => void): void => {
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
