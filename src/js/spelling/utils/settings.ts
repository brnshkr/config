/**
 * @internal @brnshkr/config/spelling
 */

import path from 'node:path';

import { doesFileExist, findNearestPackageJson, readJsonObjectFile } from '../../shared/utils/filesystem';
import { isPlainObject, objectEntries, objectKeys } from '../../shared/utils/object';

import type { AllowedLiteral, Allowlist, SpellingSettings } from '../types/options';

const EVERY_PATH = '*';
const DEFAULTS_FILE = 'defaults.json';
const LINE_SUFFIX_PATTERN = /^(?<text>.*):(?<lineNumbers>\d+(?:,\d+)*)$/v;

const findShippedDirectory = (): string => {
  const packageJsonPath = findNearestPackageJson(import.meta.dirname);

  if (packageJsonPath === undefined) {
    throw new Error('Unable to locate the shipped defaults of "@brnshkr/config".');
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
  const declaredSetting = settings[settingName];

  return Array.isArray(declaredSetting)
    ? declaredSetting.filter((entry): entry is string => typeof entry === 'string')
    : [];
};

const readStringMap = (settings: Record<string, unknown>, settingName: string): Record<string, string> => {
  const declaredSetting = settings[settingName];
  const stringMap: Record<string, string> = {};

  if (!isPlainObject(declaredSetting)) {
    return stringMap;
  }

  for (const [settingKey, entry] of objectEntries(declaredSetting)) {
    if (typeof entry === 'string') {
      stringMap[settingKey] = entry;
    }
  }

  return stringMap;
};

const readStringListMap = (settings: Record<string, unknown>, settingName: string): Record<string, string[]> => {
  const declaredSetting = settings[settingName];
  const stringListMap: Record<string, string[]> = {};

  if (!isPlainObject(declaredSetting)) {
    return stringListMap;
  }

  for (const settingKey of objectKeys(declaredSetting)) {
    stringListMap[settingKey] = readStringList(declaredSetting, settingKey);
  }

  return stringListMap;
};

const toSettings = (settings: Record<string, unknown>): SpellingSettings => ({
  fileExtensions: readStringList(settings, 'fileExtensions'),
  fileNames: readStringList(settings, 'fileNames'),
  ignorePatterns: readStringList(settings, 'ignorePatterns'),
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
  ignorePatterns: mergeLists(shippedSettings.ignorePatterns, declaredSettings.ignorePatterns),
  britishSpellings: { ...shippedSettings.britishSpellings, ...declaredSettings.britishSpellings },
  britishStems: mergeLists(shippedSettings.britishStems, declaredSettings.britishStems),
  stemSuffixes: mergeLists(shippedSettings.stemSuffixes, declaredSettings.stemSuffixes),
  allowlist: { ...shippedSettings.allowlist, ...declaredSettings.allowlist },
});

export const readSettings = (rootDirectory: string, configPath: string): SpellingSettings => {
  const shippedPath = path.join(findShippedDirectory(), DEFAULTS_FILE);
  const shippedSettings = toSettings(readSettingsFile(shippedPath));
  const settingsPath = path.resolve(rootDirectory, configPath);

  return doesFileExist(settingsPath)
    ? mergeSettings(shippedSettings, toSettings(readSettingsFile(settingsPath)))
    : shippedSettings;
};

const parseAllowedLiterals = (declaredLiterals: string[]): AllowedLiteral[] => declaredLiterals
  .map((declaredLiteral) => {
    const matchedGroups = LINE_SUFFIX_PATTERN.exec(declaredLiteral)?.groups;

    return {
      text: matchedGroups?.['text'] ?? declaredLiteral,
      lineNumbers: matchedGroups === undefined
        ? undefined
        : (matchedGroups['lineNumbers'] ?? '').split(',').map(Number),
    };
  });

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
  const literalsAllowedEverywhere = allowlist[EVERY_PATH] ?? [];

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
    allowlist[allowedPath] = parseAllowedLiterals(declaredLiterals);
  }

  rejectCoveredLiterals(allowlist);

  return allowlist;
};
