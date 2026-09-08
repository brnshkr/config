/**
 * @internal @brnshkr/config/eslint
 */

import path from 'node:path';

import {
  doesFileExist,
  readJsonObjectFile,
  readTextFile,
  toPosix,
} from '../../shared/utils/filesystem';

import { isPlainObject, objectValues } from '../../shared/utils/object';

import type { Maybe } from '../../shared/types/core';

export interface PackageExportsResolverOptions {
  packageJsonPath: string;
  distRoot?: string;
  srcRoot?: string;
  srcExtensions?: readonly string[];
}

export interface PackageExportsResolution {
  packageJsonPath: string;
  packageRoot: string;
  distRoot: string;
  srcRoots: readonly string[];
  apiSourceFiles: Set<string>;
}

const DEFAULT_DIST_ROOT = './dist';

const DEFAULT_SRC_EXTENSIONS = <const>[
  '.ts',
  '.tsx',
  '.mts',
  '.cts',
  '.d.ts',
  '.d.mts',
  '.d.cts',
  '.js',
  '.jsx',
  '.mjs',
  '.cjs',
  '.svelte',
  '.svelte.ts',
  '.svelte.js',
];

const SRC_ROOT_CANDIDATES = <const>[
  './src',
  './src/js',
  './src/main',
  './source',
  './sources',
  './lib',
  '.',
];

const REEXPORT_NAME_PART = String.raw`(?:\*(?:\s+as\s+[\p{ID_Start}$_][\p{ID_Continue}$]*)?|\{[^\}]*\})`;

const normalizeRoot = (value: string): string => toPosix(value)
  .replace(/^\.\//v, '')
  .replace(/\/$/v, '');

const collectStringEntries = (node: unknown, accumulator: string[]): void => {
  if (typeof node === 'string') {
    accumulator.push(node);

    return;
  }

  if (Array.isArray(node)) {
    for (const value of node) {
      collectStringEntries(value, accumulator);
    }

    return;
  }

  if (!isPlainObject(node)) {
    return;
  }

  for (const value of objectValues(node)) {
    collectStringEntries(value, accumulator);
  }
};

const findFirstExistingFile = (candidates: Iterable<string>): Maybe<string> => {
  for (const candidate of candidates) {
    if (doesFileExist(candidate)) {
      return toPosix(candidate);
    }
  }

  return undefined;
};

const buildSourceCandidates = (
  baseAbsolute: string,
  sourceExtensions: readonly string[],
): string[] => [
  ...sourceExtensions.map((extension) => `${baseAbsolute}${extension}`),
  ...sourceExtensions.map((extension) => path.resolve(baseAbsolute, `index${extension}`)),
];

const resolveSourceForDistributionFile = (
  distributionRelativePath: string,
  packageRoot: string,
  sourceRoot: string,
  sourceExtensions: readonly string[],
): Maybe<string> => {
  const baseAbsolute = path.resolve(
    packageRoot,
    sourceRoot,
    distributionRelativePath.replace(/(?:\.d\.[cm]?ts|\.[cm]?jsx?)$/v, ''),
  );

  return findFirstExistingFile(buildSourceCandidates(baseAbsolute, sourceExtensions));
};

const resolveImportSpecifier = (
  fromFilePath: string,
  specifier: string,
  sourceExtensions: readonly string[],
): Maybe<string> => {
  if (!specifier.startsWith('.')) {
    return undefined;
  }

  const absoluteBase = path.resolve(path.dirname(fromFilePath), specifier);
  const candidates = buildSourceCandidates(absoluteBase, sourceExtensions);

  return findFirstExistingFile(
    path.extname(absoluteBase) === '' ? candidates : [absoluteBase, ...candidates],
  );
};

const collectReexports = (
  entryPath: string,
  sourceExtensions: readonly string[],
  visitedPaths: Set<string>,
): void => {
  if (visitedPaths.has(entryPath)) {
    return;
  }

  visitedPaths.add(entryPath);

  const content = readTextFile(entryPath);

  if (content === undefined) {
    return;
  }

  const reexportPattern = new RegExp(
    String.raw`\bexport\s+(?:type\s+)?${REEXPORT_NAME_PART}\s+from\s+["'](?<specifier>[^"']+)["']`,
    'gv',
  );

  for (const match of content.matchAll(reexportPattern)) {
    const specifier = match.groups?.['specifier'];

    if (specifier === undefined) {
      continue;
    }

    const resolvedPath = resolveImportSpecifier(entryPath, specifier, sourceExtensions);

    if (resolvedPath !== undefined) {
      collectReexports(resolvedPath, sourceExtensions, visitedPaths);
    }
  }
};

const stripPrefix = (filePath: string, normalizedPrefix: string): Maybe<string> => {
  const normalizedFile = toPosix(filePath).replace(/^\.\//v, '');

  if (!normalizedFile.startsWith(normalizedPrefix)) {
    return undefined;
  }

  return normalizedFile.slice(normalizedPrefix.length);
};

const collectApiSourceFiles = (
  distributionRelativePaths: readonly string[],
  packageRoot: string,
  sourceRoots: readonly string[],
  sourceExtensions: readonly string[],
): Set<string> => {
  const apiSourceFiles = new Set<string>();

  for (const distributionRelativePath of distributionRelativePaths) {
    for (const sourceRoot of sourceRoots) {
      const resolvedPath = resolveSourceForDistributionFile(
        distributionRelativePath,
        packageRoot,
        sourceRoot,
        sourceExtensions,
      );

      if (resolvedPath !== undefined) {
        collectReexports(resolvedPath, sourceExtensions, apiSourceFiles);

        break;
      }
    }
  }

  return apiSourceFiles;
};

export const resolvePackageApiSources = (
  options: PackageExportsResolverOptions,
): Maybe<PackageExportsResolution> => {
  const packageJsonPath = path.resolve(options.packageJsonPath);
  const manifest = readJsonObjectFile(packageJsonPath);

  if (manifest?.['exports'] === undefined) {
    return undefined;
  }

  const packageRoot = path.dirname(packageJsonPath);
  const distributionRoot = options.distRoot ?? DEFAULT_DIST_ROOT;
  const sourceExtensions = options.srcExtensions ?? DEFAULT_SRC_EXTENSIONS;
  const sourceRoots = options.srcRoot === undefined ? SRC_ROOT_CANDIDATES : [options.srcRoot];
  const normalizedDistribution = `${normalizeRoot(distributionRoot)}/`;
  const exportEntries: string[] = [];

  collectStringEntries(manifest['exports'], exportEntries);

  const distributionRelativePaths = exportEntries
    .filter((value) => value.startsWith('./') && !value.includes('*'))
    .map((value) => stripPrefix(value, normalizedDistribution))
    .filter((value): value is string => value !== undefined);

  if (distributionRelativePaths.length === 0) {
    return undefined;
  }

  const apiSourceFiles = collectApiSourceFiles(
    distributionRelativePaths,
    packageRoot,
    sourceRoots,
    sourceExtensions,
  );

  if (apiSourceFiles.size === 0) {
    return undefined;
  }

  return {
    packageJsonPath: toPosix(packageJsonPath),
    packageRoot: toPosix(packageRoot),
    distRoot: distributionRoot,
    srcRoots: sourceRoots,
    apiSourceFiles,
  };
};
