/**
 * @internal @brnshkr/config/vitest
 */

import { mergeConfig } from 'vitest/config';

import { objectAssign, objectEntries, objectKeys } from '../../shared/utils/object';

import type { Maybe } from '../../shared/types/core';
import type { Config } from '../types/config';
import type { ResolvedOptions } from '../types/options';

const isValidGlobalAdditionalConfigKey = (key: string): key is keyof Config => (<const>[
  'appType',
  'assetsInclude',
  'base',
  'build',
  'builder',
  'cacheDir',
  'clearScreen',
  'css',
  'customLogger',
  'define',
  'dev',
  'envDir',
  'envPrefix',
  'environments',
  'esbuild',
  'experimental',
  'future',
  'html',
  'json',
  'legacy',
  'logLevel',
  'mode',
  'optimizeDeps',
  'plugins',
  'preview',
  'publicDir',
  'resolve',
  'root',
  'server',
  'ssr',
  'test',
  'worker',
] satisfies (keyof Config)[]).includes(key);

const getGlobalAdditionalConfig = (options: ResolvedOptions): Maybe<Config> => {
  const config: Config = {};

  for (const [key, value] of objectEntries(options)) {
    if (isValidGlobalAdditionalConfigKey(key)) {
      // eslint-disable-next-line ts/no-explicit-any, ts/no-unsafe-assignment -- The type of the value is not important here, just pass it through
      config[key] = <any>value;
    }
  }

  if (objectKeys(config).length === 0) {
    return undefined;
  }

  return config;
};

export const getUserConfigs = (
  resolvedOptions: ResolvedOptions,
  additionalConfigs: Config[],
): Config[] => [
  getGlobalAdditionalConfig(resolvedOptions),
  ...additionalConfigs,
].filter(Boolean);

export const includeConfigs = (config: Config, configsToInclude: Config[]): void => {
  for (const configToInclude of configsToInclude) {
    objectAssign(config, mergeConfig(config, configToInclude));
  }
};
