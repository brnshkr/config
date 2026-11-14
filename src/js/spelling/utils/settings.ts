/**
 * @internal @brnshkr/config
 */

import path from 'node:path';

import { compileConfiguredPattern } from '#shared/utils/configured-pattern.ts';
import { doesFileExist, findNearestPackageJson, readJsonObjectFile } from '#shared/utils/filesystem.ts';

import {
  isPlainObject,
  objectEntries,
  objectKeys,
  readOwnValue,
  writeOwnValue,
} from '#shared/utils/object.ts';

import { packageFullName } from '#shared/utils/package-json.ts';

import type { Maybe } from '#shared/types/core.ts';
import type { AllowedLiteral, Allowlist, SpellingSettings } from '#spelling/types/options.ts';

const EVERY_PATH = '*';
const DEFAULTS_FILE = 'defaults.json';
const IGNORE_PATTERN_FLAGS = 'u';

/**
 * @throws {Error}
 */
const findShippedDirectory = (): string => {
  const packageJsonPath = findNearestPackageJson(import.meta.dirname);

  if (packageJsonPath === undefined) {
    throw new Error(`Shipped defaults of "${packageFullName}" not found.`);
  }

  return path.join(path.dirname(packageJsonPath), 'conf', 'spelling');
};

/**
 * @throws {Error}
 */
const readSettingsFile = (filePath: string): Record<string, unknown> => {
  const settings = readJsonObjectFile(filePath);

  if (settings === undefined) {
    throw new Error(`Unable to read file "${filePath}".`);
  }

  return settings;
};

const readStringList = (settings: Record<string, unknown>, settingName: string): string[] => {
  const declaredSetting = readOwnValue(settings, settingName);

  return Array.isArray(declaredSetting)
    ? declaredSetting.filter((entry): entry is string => typeof entry === 'string')
    : [];
};

const readStringMap = (settings: Record<string, unknown>, settingName: string): Record<string, string> => {
  const declaredSetting = readOwnValue(settings, settingName);
  const stringMap: Record<string, string> = {};

  if (!isPlainObject(declaredSetting)) {
    return stringMap;
  }

  for (const [settingKey, entry] of objectEntries(declaredSetting)) {
    if (typeof entry === 'string') {
      writeOwnValue(stringMap, settingKey, entry);
    }
  }

  return stringMap;
};

const readStringListMap = (settings: Record<string, unknown>, settingName: string): Record<string, string[]> => {
  const declaredSetting = readOwnValue(settings, settingName);
  const stringListMap: Record<string, string[]> = {};

  if (!isPlainObject(declaredSetting)) {
    return stringListMap;
  }

  for (const settingKey of objectKeys(declaredSetting)) {
    writeOwnValue(stringListMap, settingKey, readStringList(declaredSetting, settingKey));
  }

  return stringListMap;
};

// eslint-disable-next-line security/detect-non-literal-regexp -- Shipped patterns are ours
const compileShippedPattern = (source: string): RegExp => new RegExp(source, IGNORE_PATTERN_FLAGS);

/**
 * @throws {Error}
 */
const compileDeclaredPattern = (pattern: string): Maybe<RegExp> => compileConfiguredPattern(
  pattern,
  IGNORE_PATTERN_FLAGS,
);

const toSettings = (
  settings: Record<string, unknown>,
  compileIgnorePattern: (pattern: string) => Maybe<RegExp>,
): SpellingSettings => ({
  fileExtensions: readStringList(settings, 'fileExtensions'),
  fileNames: readStringList(settings, 'fileNames'),
  ignorePatterns: readStringList(settings, 'ignorePatterns').flatMap((pattern) => compileIgnorePattern(pattern) ?? []),
  britishSpellings: readStringMap(settings, 'britishSpellings'),
  britishStems: readStringList(settings, 'britishStems'),
  stemSuffixes: readStringList(settings, 'stemSuffixes'),
  allowlist: readStringListMap(settings, 'allowlist'),
});

const mergeLists = (shippedEntries: string[], declaredEntries: string[]): string[] => [
  ...new Set([
    ...shippedEntries,
    ...declaredEntries,
  ]),
];

const mergeAllowlists = (
  shippedAllowlist: Record<string, string[]> = {},
  declaredAllowlist: Record<string, string[]> = {},
): Record<string, string[]> => {
  const mergedAllowlist = { ...shippedAllowlist };

  for (const [allowedPath, declaredLiterals] of objectEntries(declaredAllowlist)) {
    writeOwnValue(
      mergedAllowlist,
      allowedPath,
      mergeLists(readOwnValue(mergedAllowlist, allowedPath) ?? [], declaredLiterals),
    );
  }

  return mergedAllowlist;
};

const mergeSettings = (
  shippedSettings: SpellingSettings,
  declaredSettings: SpellingSettings,
): SpellingSettings => ({
  fileExtensions: mergeLists(shippedSettings.fileExtensions, declaredSettings.fileExtensions),
  fileNames: mergeLists(shippedSettings.fileNames, declaredSettings.fileNames),
  ignorePatterns: [
    ...shippedSettings.ignorePatterns,
    ...declaredSettings.ignorePatterns,
  ],
  britishSpellings: {
    ...shippedSettings.britishSpellings,
    ...declaredSettings.britishSpellings,
  },
  britishStems: mergeLists(shippedSettings.britishStems, declaredSettings.britishStems),
  stemSuffixes: mergeLists(shippedSettings.stemSuffixes, declaredSettings.stemSuffixes),
  allowlist: mergeAllowlists(shippedSettings.allowlist, declaredSettings.allowlist),
});

/**
 * @throws {Error}
 */
export const readSettings = (rootDirectory: string, configPath: string): SpellingSettings => {
  const shippedPath = path.join(findShippedDirectory(), DEFAULTS_FILE);
  const shippedSettings = toSettings(readSettingsFile(shippedPath), compileShippedPattern);
  const settingsPath = path.resolve(rootDirectory, configPath);

  return doesFileExist(settingsPath)
    ? mergeSettings(shippedSettings, toSettings(readSettingsFile(settingsPath), compileDeclaredPattern))
    : shippedSettings;
};

const parseLineNumbers = (lineSuffix: string): Maybe<number[]> => {
  const lineNumbers = lineSuffix.split(',');

  return lineNumbers.every((lineNumber) => /^\d+$/v.test(lineNumber))
    ? lineNumbers.map(Number)
    : undefined;
};

const parseAllowedLiteral = (declaredLiteral: string): AllowedLiteral => {
  const separatorIndex = declaredLiteral.lastIndexOf(':');

  const lineNumbers = separatorIndex === -1
    ? undefined
    : parseLineNumbers(declaredLiteral.slice(separatorIndex + 1));

  return {
    text: lineNumbers === undefined
      ? declaredLiteral
      : declaredLiteral.slice(0, separatorIndex),
    lineNumbers,
  };
};

const isCoveredBy = (coveringCandidate: AllowedLiteral, allowedLiteral: AllowedLiteral): boolean => {
  if (coveringCandidate.text.toLowerCase() !== allowedLiteral.text.toLowerCase()) {
    return false;
  }

  if (coveringCandidate.lineNumbers === undefined) {
    return true;
  }

  return allowedLiteral.lineNumbers?.every(
    (lineNumber) => coveringCandidate.lineNumbers?.includes(lineNumber) === true,
  ) ?? false;
};

const formatAllowedLiteral = ({ text, lineNumbers }: AllowedLiteral): string => (lineNumbers === undefined
  ? text
  : `${text}:${lineNumbers.join(',')}`);

const doesGlobMatch = (glob: string, filePath: string): boolean => {
  if (!glob.startsWith('*')) {
    return glob === ''
      ? filePath === ''
      : (glob.slice(0, 1) === filePath.slice(0, 1) && doesGlobMatch(glob.slice(1), filePath.slice(1)));
  }

  const isCrossingDirectories = glob.startsWith('**');
  const remainingGlob = glob.slice(isCrossingDirectories ? '**'.length : '*'.length);

  for (let offset = 0; offset <= filePath.length; offset += 1) {
    if (!isCrossingDirectories && filePath.slice(0, offset).includes('/')) {
      return false;
    }

    if (doesGlobMatch(remainingGlob, filePath.slice(offset))) {
      return true;
    }
  }

  return false;
};

export const getLiteralsAllowedIn = (
  allowlist: Allowlist,
  filePath: string,
  skippedPath?: string,
): AllowedLiteral[] => objectEntries(allowlist)
  .filter(([allowedPath]) => allowedPath !== skippedPath
    && (allowedPath === EVERY_PATH || doesGlobMatch(allowedPath, filePath)))
  .flatMap(([, allowedLiterals]) => allowedLiterals);

/**
 * @throws {Error}
 */
const rejectCoveredLiterals = (allowlist: Allowlist): void => {
  for (const [allowedPath, allowedLiterals] of objectEntries(allowlist)) {
    const broaderLiterals = getLiteralsAllowedIn(allowlist, allowedPath, allowedPath);

    for (const [index, allowedLiteral] of allowedLiterals.entries()) {
      const coveringLiteral = [
        ...broaderLiterals,
        ...allowedLiterals.slice(0, index),
      ]
        .find((coveringCandidate) => isCoveredBy(coveringCandidate, allowedLiteral));

      if (coveringLiteral !== undefined) {
        throw new Error(
          `Allowed word "${formatAllowedLiteral(allowedLiteral)}" under "${allowedPath}" `
          + `is already covered by "${formatAllowedLiteral(coveringLiteral)}".`,
        );
      }
    }
  }
};

/**
 * @throws {Error}
 */
export const readAllowlist = (settings: SpellingSettings): Allowlist => {
  const allowlist: Allowlist = {};
  const allowlistEntries = objectEntries(settings.allowlist ?? {});

  for (const [allowedPath, declaredLiterals] of allowlistEntries) {
    writeOwnValue(
      allowlist,
      allowedPath,
      declaredLiterals.map((declaredLiteral) => parseAllowedLiteral(declaredLiteral)),
    );
  }

  rejectCoveredLiterals(allowlist);

  return allowlist;
};
