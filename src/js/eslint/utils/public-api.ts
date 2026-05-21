import path from 'node:path';

import { findNearestPackageJson, getMtime, toPosix } from '../../shared/utils/filesystem';

import { resolvePackageApiSources } from './package-exports';

import type { Maybe } from '../../shared/types/core';
import type { PackageExportsResolution, PackageExportsResolverOptions } from './package-exports';

interface ResolutionCacheEntry {
  mtime: Maybe<number>;
  resolution: Maybe<PackageExportsResolution>;
}

const resolutionCache = new Map<string, ResolutionCacheEntry>();

const getCacheKey = (options: Partial<PackageExportsResolverOptions>, cwd: string): string => JSON.stringify({
  cwd,
  packageJsonPath: options.packageJsonPath,
  distRoot: options.distRoot,
  srcRoot: options.srcRoot,
  srcExtensions: options.srcExtensions,
});

export const loadPublicApiResolution = (
  options: Partial<PackageExportsResolverOptions>,
  cwd: string,
): Maybe<PackageExportsResolution> => {
  const cacheKey = getCacheKey(options, cwd);
  const packageJsonPath = options.packageJsonPath ?? findNearestPackageJson(cwd);
  const mtime = packageJsonPath === undefined ? undefined : getMtime(packageJsonPath);
  const cacheEntry = resolutionCache.get(cacheKey);

  if (cacheEntry !== undefined && cacheEntry.mtime === mtime) {
    return cacheEntry.resolution;
  }

  const resolution = packageJsonPath === undefined
    ? undefined
    : resolvePackageApiSources({
      packageJsonPath,
      distRoot: options.distRoot,
      srcRoot: options.srcRoot,
      srcExtensions: options.srcExtensions,
    });

  resolutionCache.set(cacheKey, {
    mtime,
    resolution,
  });

  return resolution;
};

export const isPublicApiFile = (
  options: PackageExportsResolverOptions,
  cwd: string,
  filename: string,
): boolean => {
  const resolution = loadPublicApiResolution(options, cwd);

  return resolution === undefined
    ? false
    : resolution.apiSourceFiles.has(toPosix(path.resolve(filename)));
};

export const clearPublicApiResolutionCache = (): void => {
  resolutionCache.clear();
};
