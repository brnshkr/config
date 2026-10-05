/**
 * @internal @brnshkr/config/eslint
 */

import path from 'node:path';

import {
  findNearestPackageJson,
  getMtime,
  readJsonObjectFile,
  toPosix,
} from '../../shared/utils/filesystem';

import { isPlainObject, objectEntries, objectFromEntries } from '../../shared/utils/object';

import type { Maybe } from '../../shared/types/core';
import type { TsConfigPaths } from './tsconfig';

interface CachedManifest {
  mtime: Maybe<number>;
  importsField: unknown;
}

const BUNDLER_CONDITIONS = <const>[
  'types',
  'import',
  'default',
];

const manifestCache = new Map<string, CachedManifest>();

const resolveConditionalTarget = (target: unknown, activeConditions: ReadonlySet<string>): Maybe<string> => {
  if (typeof target === 'string') {
    return target;
  }

  if (!isPlainObject(target)) {
    return undefined;
  }

  for (const [condition, nestedTarget] of objectEntries(target)) {
    const resolvedTarget = activeConditions.has(condition)
      ? resolveConditionalTarget(nestedTarget, activeConditions)
      : undefined;

    if (resolvedTarget !== undefined) {
      return resolvedTarget;
    }
  }

  return undefined;
};

export const loadPackageImports = (directory: string, customConditions: readonly string[] = []): TsConfigPaths => {
  const packageJsonPath = findNearestPackageJson(directory);

  if (packageJsonPath === undefined) {
    return {};
  }

  const mtime = getMtime(packageJsonPath);
  const cachedManifest = manifestCache.get(packageJsonPath);

  const importsField = (cachedManifest !== undefined && cachedManifest.mtime === mtime)
    ? cachedManifest.importsField
    : readJsonObjectFile(packageJsonPath)?.['imports'];

  manifestCache.set(packageJsonPath, { mtime, importsField });

  if (!isPlainObject(importsField)) {
    return {};
  }

  const packageRoot = path.dirname(packageJsonPath);
  const activeConditions = new Set([...BUNDLER_CONDITIONS, ...customConditions]);

  return objectFromEntries(objectEntries(importsField).flatMap(([pattern, target]) => {
    const resolvedTarget = resolveConditionalTarget(target, activeConditions);

    return resolvedTarget === undefined ? [] : [[pattern, [toPosix(path.resolve(packageRoot, resolvedTarget))]]];
  }));
};
