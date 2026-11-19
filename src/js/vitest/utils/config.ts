/**
 * @internal @brnshkr/config/vitest
 */

import { mergeConfig } from 'vitest/config';

import { objectAssign, pickKeys } from '#shared/utils/object.ts';

import type { Maybe } from '#shared/types/core.ts';
import type { Config } from '#vitest/types/config.ts';
import type { ResolvedOptions } from '#vitest/types/options.ts';

const GLOBAL_ADDITIONAL_CONFIG_KEYS = <const>[
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
  'devtools',
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
  'oxc',
  'plugins',
  'preview',
  'publicDir',
  'resolve',
  'root',
  'server',
  'ssr',
  'test',
  'tsconfig',
  'worker',
] satisfies (keyof Config)[];

const getGlobalAdditionalConfig = (
  options: ResolvedOptions,
): Maybe<Config> => pickKeys(options, GLOBAL_ADDITIONAL_CONFIG_KEYS);

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
