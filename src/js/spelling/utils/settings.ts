/**
 * @internal @brnshkr/config/spelling
 */

import path from 'node:path';

import { compileConfiguredPattern } from '../../shared/utils/configured-pattern';
import { doesFileExist, findNearestPackageJson, readJsonObjectFile } from '../../shared/utils/filesystem';

import {
  isPlainObject,
  objectEntries,
  objectKeys,
  readOwnValue,
  writeOwnValue,
} from '../../shared/utils/object';

import { packageFullName } from '../../shared/utils/package-json';

import type { Maybe } from '../../shared/types/core';
import type { AllowedLiteral, Allowlist, SpellingSettings } from '../types/options';

const EVERY_PATH = '*';
const DEFAULTS_FILE = 'defaults.json';
const IGNORE_PATTERN_FLAGS = 'u';

const findShippedDirectory = (): string => {
  const packageJsonPath = findNearestPackageJson(import.meta.dirname);

  if (packageJsonPath === undefined) {
    throw new Error(`Unable to locate the shipped defaults of "${packageFullName}".`);
  }

  return path.join(path.dirname(packageJsonPath), 'conf', 'spelling');
};

const readSettingsFile = (filePath: string): Record<string, unknown> => {
  const settings = readJsonObjectFile(filePath);

  if (settings === undefined) {
    throw new Error(`Unable to read "${filePath}".`);
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
  ...new Set([...shippedEntries, ...declaredEntries]),
];

const mergeSettings = (
  shippedSettings: SpellingSettings,
  declaredSettings: SpellingSettings,
): SpellingSettings => ({
  fileExtensions: mergeLists(shippedSettings.fileExtensions, declaredSettings.fileExtensions),
  fileNames: mergeLists(shippedSettings.fileNames, declaredSettings.fileNames),
  ignorePatterns: [...shippedSettings.ignorePatterns, ...declaredSettings.ignorePatterns],
  britishSpellings: { ...shippedSettings.britishSpellings, ...declaredSettings.britishSpellings },
  britishStems: mergeLists(shippedSettings.britishStems, declaredSettings.britishStems),
  stemSuffixes: mergeLists(shippedSettings.stemSuffixes, declaredSettings.stemSuffixes),
  allowlist: { ...shippedSettings.allowlist, ...declaredSettings.allowlist },
});

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

  return lineNumbers.every((lineNumber) => /^\d+$/v.test(lineNumber)) ? lineNumbers.map(Number) : undefined;
};

const parseAllowedLiteral = (declaredLiteral: string): AllowedLiteral => {
  const separatorIndex = declaredLiteral.lastIndexOf(':');
  const lineNumbers = separatorIndex === -1 ? undefined : parseLineNumbers(declaredLiteral.slice(separatorIndex + 1));

  return {
    text: lineNumbers === undefined ? declaredLiteral : declaredLiteral.slice(0, separatorIndex),
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

const rejectCoveredLiterals = (allowlist: Allowlist): void => {
  const literalsAllowedEverywhere = readOwnValue(allowlist, EVERY_PATH) ?? [];

  for (const [allowedPath, allowedLiterals] of objectEntries(allowlist)) {
    for (const [index, allowedLiteral] of allowedLiterals.entries()) {
      const broaderLiterals = allowedPath === EVERY_PATH ? [] : literalsAllowedEverywhere;

      const coveringLiteral = [...broaderLiterals, ...allowedLiterals.slice(0, index)]
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
