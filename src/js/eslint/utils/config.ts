/**
 * @internal @brnshkr/config/eslint
 */

import { MAIN_SCOPES, SUB_SCOPES } from '#eslint/types/scopes.ts';

import {
  objectEntries,
  objectFromEntries,
  objectKeys,
  pickKeys,
} from '#shared/utils/object.ts';

import { packageOrganization } from '#shared/utils/package-json.ts';

import type { Config, ResolvableConfig } from '#eslint/types/config.ts';
import type { ResolvedOptions } from '#eslint/types/options.ts';
import type { MainScope, SubScope } from '#eslint/types/scopes.ts';
import type { Awaitable, Maybe } from '#shared/types/core.ts';

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
): Record<string, any> => objectFromEntries(
  objectEntries(rules ?? {}).map(([key, value]) => {
    for (const [from, to] of objectEntries(map)) {
      if (key.startsWith(`${from}/`)) {
        return [to + key.slice(from.length), value];
      }
    }

    return [key, value];
  }),
);
/* eslint-enable ts/no-explicit-any -- Restore rule */

const GLOBAL_ADDITIONAL_CONFIG_KEYS = <const>[
  'name',
  'languageOptions',
  'linterOptions',
  'processor',
  'plugins',
  'rules',
  'settings',
] satisfies (keyof Omit<Config, 'ignores' | 'files'>)[];

const getGlobalAdditionalConfig = (options: ResolvedOptions): Maybe<Config> => {
  const config = pickKeys(options, GLOBAL_ADDITIONAL_CONFIG_KEYS);

  if (config !== undefined) {
    config.name ??= buildConfigName(MAIN_SCOPES.USERLAND, SUB_SCOPES.GLOBAL);
  }

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

    validConfigs.push(objectKeys(config).length > 0 ? config : undefined);

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
