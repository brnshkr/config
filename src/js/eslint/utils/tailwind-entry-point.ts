/**
 * @internal @brnshkr/config/eslint
 */

import { execFile } from 'node:child_process';
import path from 'node:path';

import { readTextFile, toPosix } from '#shared/utils/filesystem.ts';
import { GLOB_IGNORES } from '#shared/utils/globs.ts';
import { joinAsQuotedList } from '#shared/utils/string.ts';

import type { Maybe } from '#shared/types/core.ts';

const listStylesheetPaths = async (rootDirectory: string): Promise<Maybe<string[]>> => new Promise((resolve) => {
  execFile('git', [
    'ls-files',
    '-z',
    '--cached',
    '--others',
    '--exclude-standard',
    '--',
    '*.css',
  ], {
    cwd: rootDirectory,
    encoding: 'utf-8',
    maxBuffer: Infinity,
  }, (error, stdout) => {
    resolve(Error.isError(error) ? undefined : stdout.split('\0').filter((filePath) => filePath !== ''));
  });
});

const readImportSpecifiers = (stylesheetPath: string): string[] => (readTextFile(stylesheetPath) ?? '')
  .matchAll(/@import\s+(?:url\(\s*)?(?<quote>["'])(?<specifier>[^"']+)\k<quote>/gv)
  .map((match) => match.groups?.['specifier'])
  .filter((specifier) => specifier !== undefined)
  .toArray();

/**
 * @rejects {Error}
 */
export const findTailwindEntryPoint = async (rootDirectory: string): Promise<Maybe<string>> => {
  const listedStylesheetPaths = await listStylesheetPaths(rootDirectory);

  const stylesheetPaths = listedStylesheetPaths?.filter(
    (stylesheetPath) => GLOB_IGNORES.every((glob) => !path.matchesGlob(stylesheetPath, glob)),
  );

  if (stylesheetPaths === undefined) {
    return undefined;
  }

  const importsByPath = new Map(stylesheetPaths.map((stylesheetPath) => <const>[
    stylesheetPath,
    readImportSpecifiers(path.join(rootDirectory, stylesheetPath)),
  ]));

  const importedPaths = new Set([...importsByPath].flatMap(([stylesheetPath, specifiers]) => specifiers
    .filter((specifier) => specifier.startsWith('.'))
    .map((specifier) => toPosix(path.join(path.dirname(stylesheetPath), specifier)))));

  const entryPaths = [...importsByPath]
    .filter(([stylesheetPath, specifiers]) => specifiers.includes('tailwindcss') && !importedPaths.has(stylesheetPath))
    .map(([stylesheetPath]) => stylesheetPath);

  if (entryPaths.length === 0) {
    throw new Error('Unable to find a stylesheet importing "tailwindcss", set "tailwind.entryPoint".');
  }

  if (entryPaths.length > 1) {
    throw new Error(
      `Several stylesheets import "tailwindcss": ${joinAsQuotedList(entryPaths)}, set "tailwind.entryPoint".`,
    );
  }

  return entryPaths[0];
};
