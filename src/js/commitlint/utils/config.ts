/**
 * @internal @brnshkr/config/commitlint
 */

import {
  createConfigMerger,
  merge,
  replace,
  union,
} from '#shared/utils/config-merger.ts';

import { objectEntries, objectFromEntries } from '#shared/utils/object.ts';

import type { Config } from '#commitlint/types/config.ts';
import type { MergeStrategies } from '#shared/utils/config-merger.ts';

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

const MERGE_STRATEGIES = <const>{
  extends: union,
  formatter: replace,
  rules: merge,
  parserPreset: replace,
  ignores: union,
  defaultIgnores: replace,
  plugins: (currentPlugins, pluginsToInclude) => mergeInlinePlugins(union(currentPlugins, pluginsToInclude)),
  helpUrl: replace,
  prompt: merge,
} satisfies MergeStrategies<Config>;

export const { getUserConfigs, includeConfigs } = createConfigMerger<Config>(MERGE_STRATEGIES);
