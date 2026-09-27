/**
 * @internal @brnshkr/config/commitlint
 */

import { objectEntries, objectFromEntries, pickKeys } from '../../shared/utils/object';

import type { Maybe } from '../../shared/types/core';
import type { Config } from '../types/config';
import type { ResolvedOptions } from '../types/options';

const GLOBAL_ADDITIONAL_CONFIG_KEYS = <const>[
  'extends',
  'formatter',
  'rules',
  'parserPreset',
  'ignores',
  'defaultIgnores',
  'plugins',
  'helpUrl',
  'prompt',
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

const mergeInlinePlugins = (pluginsToMerge: NonNullable<Config['plugins']>): NonNullable<Config['plugins']> => {
  const namedPlugins = pluginsToMerge.filter((pluginToMerge) => typeof pluginToMerge === 'string');
  const inlinePlugins = pluginsToMerge.filter((pluginToMerge) => typeof pluginToMerge !== 'string');

  return inlinePlugins.length === 0
    ? namedPlugins
    : [
      ...namedPlugins,
      {
        rules: objectFromEntries(inlinePlugins.flatMap((inlinePlugin) => objectEntries(inlinePlugin.rules))),
      },
    ];
};

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
      config.plugins = mergeInlinePlugins([...new Set([
        ...config.plugins ?? [],
        ...configToInclude.plugins,
      ])]);
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
