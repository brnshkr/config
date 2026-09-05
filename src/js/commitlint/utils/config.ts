/**
 * @internal @brnshkr/config/commitlint
 */

import { objectEntries, objectKeys } from '../../shared/utils/object';

import type { Maybe } from '../../shared/types/core';
import type { Config } from '../types/config';
import type { ResolvedOptions } from '../types/options';

const isValidGlobalAdditionalConfigKey = (key: string): key is keyof Config => (<const>[
  'extends',
  'formatter',
  'rules',
  'parserPreset',
  'ignores',
  'defaultIgnores',
  'plugins',
  'helpUrl',
  'prompt',
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

// eslint-disable-next-line complexity -- Extracting these assignments to separate functions would not improve readability
export const includeConfigs = (config: Config, configsToInclude: Config[]): void => {
  for (const configToInclude of configsToInclude) {
    if (configToInclude.extends !== undefined) {
      config.extends = [...new Set([
        ...(Array.isArray(config.extends)
          ? (config.extends ?? [])
          : [config.extends].filter(Boolean)),
        ...(Array.isArray(configToInclude.extends)
          ? (configToInclude.extends ?? [])
          : [configToInclude.extends].filter(Boolean)),
      ])];
    }

    if (configToInclude.formatter !== undefined) {
      config.formatter = configToInclude.formatter;
    }

    if (configToInclude.rules !== undefined) {
      config.rules = {
        ...config.rules,
        ...configToInclude.rules,
      };
    }

    if (configToInclude.parserPreset !== undefined) {
      config.parserPreset = configToInclude.parserPreset;
    }

    if (configToInclude.ignores !== undefined) {
      config.ignores = [...new Set([
        ...config.ignores ?? [],
        ...configToInclude.ignores,
      ])];
    }

    if (configToInclude.defaultIgnores !== undefined) {
      config.defaultIgnores = configToInclude.defaultIgnores;
    }

    if (configToInclude.plugins !== undefined) {
      config.plugins = [...new Set([
        ...config.plugins ?? [],
        ...configToInclude.plugins,
      ])];
    }

    if (configToInclude.helpUrl !== undefined) {
      config.helpUrl = configToInclude.helpUrl;
    }

    if (configToInclude.prompt !== undefined) {
      config.prompt = {
        ...config.prompt,
        ...configToInclude.prompt,
      };
    }
  }
};
