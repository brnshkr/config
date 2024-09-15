import { objectEntries } from '../../shared/utils/object';
import { packageOrganization } from '../../shared/utils/package-json';
import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';

import type { Awaitable, Maybe } from '../../shared/types/core';
import type { Config, ResolvableConfig } from '../types/config';
import type { ResolvedOptions } from '../types/options';
import type { MainScope, SubScope } from '../types/scopes';

export const buildConfigName = (
  mainScope: MainScope,
  subScope: SubScope,
): string => [packageOrganization, mainScope, subScope]
  .filter(Boolean)
  .join('/');

/* eslint-disable ts/no-explicit-any -- Explicitly use any to allow for a wide range of differently typed rule records */
export const renameRules = (
  rules: Maybe<Record<string, any>>,
  map: Record<string, string>,
): Record<string, any> => Object.fromEntries(
  Object.entries(rules ?? {}).map(([key, value]) => {
    for (const [from, to] of Object.entries(map)) {
      if (key.startsWith(`${from}/`)) {
        return [to + key.slice(from.length), value];
      }
    }

    return [key, value];
  }),
);
/* eslint-enable ts/no-explicit-any -- Restore rule */

const isValidGlobalAdditionalConfigKey = (
  key: string,
): key is keyof Omit<Config, 'ignores' | 'files'> => (<const>[
  'name',
  'languageOptions',
  'linterOptions',
  'processor',
  'plugins',
  'rules',
  'settings',
] satisfies (keyof Omit<Config, 'ignores' | 'files'>)[]).includes(key);

const getGlobalAdditionalConfig = (options: ResolvedOptions): Maybe<Config> => {
  const config: Config = {};

  for (const [key, value] of objectEntries(options)) {
    if (isValidGlobalAdditionalConfigKey(key)) {
      // eslint-disable-next-line ts/no-explicit-any, ts/no-unsafe-assignment -- The type of the value is not important here, just pass it through
      config[key] = <any>value;
    }
  }

  if (Object.keys(config).length === 0) {
    return undefined;
  }

  config.name ??= buildConfigName(MAIN_SCOPES.USERLAND, SUB_SCOPES.GLOBAL);

  return config;
};

const ensureNamesForSyncAdditionalConfigs = (
  additionalConfigs: Awaitable<Config>[],
): Maybe<Awaitable<Config>>[] => {
  const validConfigs: Maybe<Awaitable<Config>>[] = [];
  let currentIndex = 1;

  for (const config of additionalConfigs) {
    if (config instanceof Promise
      || (typeof config.name === 'string' && config.name.trim().length > 0)) {
      validConfigs.push(config);

      continue;
    }

    validConfigs.push(Object.keys(config).length > 0 ? config : undefined);

    config.name = buildConfigName(MAIN_SCOPES.USERLAND, `${SUB_SCOPES.UNNAMED}-${String(currentIndex)}`);
    currentIndex += 1;
  }

  return validConfigs;
};

export const getUserConfigs = (
  resolvedOptions: ResolvedOptions,
  additionalConfigs: Awaitable<Config>[],
): ResolvableConfig[] => [
  getGlobalAdditionalConfig(resolvedOptions),
  ...ensureNamesForSyncAdditionalConfigs(additionalConfigs),
];
