/**
 * @internal @brnshkr/config/eslint
 */

import fs from 'node:fs';
import path from 'node:path';

import { objectEntries, objectFromEntries } from '../../shared/utils/object';

import type { Maybe } from '../../shared/types/core';
import type { TypescriptOptions } from '../types/options';

export type TsConfigPaths = Record<string, string[]>;

interface ParsedTsConfig {
  // eslint-disable-next-line brnshkr/boolish-prefix -- Mirrors the `extends` key of `tsconfig.json`
  extends?: string | string[];
  compilerOptions?: {
    baseUrl?: string;
    paths?: Record<string, string[]>;
  };
}

const DEFAULT_TSCONFIG_FILENAME = 'tsconfig.json';
const JSONC_TOKEN_PATTERN = /"(?:[^"\\]|\\.)*"|\/\/[^\n]*|\/\*[\s\S]*?\*\//gv;
const TRAILING_COMMA_PATTERN = /,(?=\s*[\]\}])/gv;

const stripJsonc = (input: string): string => input
  .replaceAll(JSONC_TOKEN_PATTERN, (match) => (match.startsWith('"') ? match : ''))
  .replaceAll(TRAILING_COMMA_PATTERN, '');

const parseTsConfigFile = (filePath: string): Maybe<ParsedTsConfig> => {
  try {
    // eslint-disable-next-line node/no-sync -- Sync read keeps the rule create() function synchronous as ESLint requires
    const content = fs.readFileSync(filePath, 'utf-8');

    return <ParsedTsConfig>JSON.parse(stripJsonc(content));
  } catch {
    return undefined;
  }
};

const resolveExtends = (parentConfig: string, fromDirectory: string): string => {
  if (parentConfig.startsWith('.') || path.isAbsolute(parentConfig)) {
    const resolved = path.resolve(fromDirectory, parentConfig);

    return path.extname(resolved) === '' ? `${resolved}.json` : resolved;
  }

  return path.resolve(fromDirectory, 'node_modules', parentConfig);
};

const normalizeExtends = (raw: ParsedTsConfig): string[] => {
  if (raw.extends === undefined) {
    return [];
  }

  return Array.isArray(raw.extends) ? raw.extends : [raw.extends];
};

const loadInternal = (filePath: string, visitedPaths: Set<string>): Maybe<ParsedTsConfig> => {
  if (visitedPaths.has(filePath)) {
    return undefined;
  }

  visitedPaths.add(filePath);

  const parsedConfig = parseTsConfigFile(filePath);

  if (!parsedConfig) {
    return undefined;
  }

  const fromDirectory = path.dirname(filePath);
  let compilerOptions: ParsedTsConfig['compilerOptions'] = {};

  for (const value of normalizeExtends(parsedConfig)) {
    compilerOptions = {
      ...compilerOptions,
      ...loadInternal(resolveExtends(value, fromDirectory), visitedPaths)?.compilerOptions,
    };
  }

  return {
    compilerOptions: {
      ...compilerOptions,
      ...parsedConfig.compilerOptions,
    },
  };
};

const cache = new Map<string, Maybe<TsConfigPaths>>();
const toPosix = (filePath: string): string => filePath.replaceAll('\\', '/');

export const loadTsConfigPaths = (tsConfigPath: string): Maybe<TsConfigPaths> => {
  const absolutePath = path.resolve(tsConfigPath);

  if (cache.has(absolutePath)) {
    return cache.get(absolutePath);
  }

  const mergedConfig = loadInternal(absolutePath, new Set());
  const paths = mergedConfig?.compilerOptions?.paths;

  if (!paths) {
    cache.set(absolutePath, undefined);

    return undefined;
  }

  const baseUrl = path.resolve(path.dirname(absolutePath), mergedConfig.compilerOptions?.baseUrl ?? '.');

  const result = objectFromEntries(objectEntries(paths).map(([pattern, targets]) => [
    pattern,
    targets.map((target) => toPosix(path.resolve(baseUrl, target))),
  ]));

  cache.set(absolutePath, result);

  return result;
};

export const clearTsConfigPathsCache = (): void => {
  cache.clear();
};

export const doesTsConfigExist = (tsConfigPath: string): boolean => {
  try {
    // eslint-disable-next-line node/no-sync -- Sync check keeps callers free from awaits and mirrors the sync loader
    fs.accessSync(tsConfigPath, fs.constants.R_OK);

    return true;
  } catch {
    return false;
  }
};

export const resolveTsConfigPath = (typescriptOptions?: Partial<TypescriptOptions>): string => {
  let tsconfig = DEFAULT_TSCONFIG_FILENAME;

  if (typescriptOptions) {
    if (typeof typescriptOptions.typeAware === 'string') {
      tsconfig = typescriptOptions.typeAware;
    } else if (typeof typescriptOptions.typeAware === 'object') {
      tsconfig = typescriptOptions.typeAware.tsconfig ?? DEFAULT_TSCONFIG_FILENAME;
    }
  }

  return path.resolve(process.cwd(), tsconfig);
};
