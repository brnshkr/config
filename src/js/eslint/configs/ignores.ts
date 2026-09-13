/**
 * @internal @brnshkr/config/eslint
 */

import path from 'node:path';

import { includeIgnoreFile } from 'eslint/config';

import { doesFileExist } from '../../shared/utils/filesystem';
import { GLOB_IGNORES } from '../../shared/utils/globs';
import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';

import type { Config } from '../types/config';

const DEFAULT_IGNORE_FILE = '.gitignore';
const isIgnoreFile = (customIgnore: string): boolean => /^\.[\w\-]+ignore$/v.test(path.basename(customIgnore));

// eslint-disable-next-line brnshkr/boolish-prefix -- Config builders are named after the config section they build
export const ignores = (customIgnores: string[] = []): Config[] => {
  const ignoreFiles = new Set([
    DEFAULT_IGNORE_FILE,
    ...customIgnores.filter((customIgnore) => isIgnoreFile(customIgnore)),
  ]);

  const existingIgnoreFiles = [...ignoreFiles]
    .map((ignoreFile) => path.resolve(ignoreFile))
    .filter((ignoreFile) => doesFileExist(ignoreFile));

  return [
    {
      name: buildConfigName(MAIN_SCOPES.IGNORES, SUB_SCOPES.BASE),
      ignores: [
        ...GLOB_IGNORES,
        ...customIgnores.filter((customIgnore) => !isIgnoreFile(customIgnore)),
      ],
    },
    ...existingIgnoreFiles.map((ignoreFile) => ({
      ...includeIgnoreFile(ignoreFile, {
        gitignoreResolution: true,
      }),
      name: buildConfigName(MAIN_SCOPES.IGNORES, SUB_SCOPES.FILES),
    })),
  ];
};
