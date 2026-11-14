/**
 * @internal @brnshkr/config
 */

import {
  objectEntries,
  pickKeys,
  readOwnValue,
  writeOwnValue,
} from '#shared/utils/object.ts';

import type { Maybe } from '#shared/types/core.ts';
import type { AnyRecord } from '#shared/utils/object.ts';

type MergeFunction<TValue> = (currentValue: Maybe<TValue>, valueToInclude: TValue) => TValue;

export type MergeStrategies<TConfig> = {
  [TKey in keyof TConfig]-?: MergeFunction<NonNullable<TConfig[TKey]>>;
};

interface ConfigMerger<TConfig> {
  getUserConfigs: (resolvedOptions: TConfig, additionalConfigs: TConfig[]) => TConfig[];
  includeConfigs: (config: TConfig, configsToInclude: TConfig[]) => void;
}

export const replace = <TValue>(_currentValue: Maybe<TValue>, valueToInclude: TValue): TValue => valueToInclude;

export const merge = <TValue extends object>(currentValue: Maybe<TValue>, valueToInclude: TValue): TValue => ({
  ...currentValue,
  ...valueToInclude,
});

// eslint-disable-next-line ts/no-unsafe-type-assertion -- A union of arrays stays an array
export const union = <TValue>(currentValue: Maybe<TValue>, valueToInclude: TValue): TValue => <TValue>[
  ...new Set([currentValue ?? [], valueToInclude].flat()),
];

export const createConfigMerger = <TConfig extends object>(
  strategies: MergeStrategies<TConfig>,
): ConfigMerger<TConfig> => {
  // eslint-disable-next-line ts/no-unsafe-type-assertion -- Strategies are keyed by config keys
  const strategyEntries = <[keyof TConfig, MergeFunction<unknown>][]>objectEntries(<AnyRecord>strategies);
  const configKeys = strategyEntries.map(([key]) => key);

  return {
    getUserConfigs: (resolvedOptions, additionalConfigs): TConfig[] => [
      <Maybe<TConfig>>pickKeys(resolvedOptions, configKeys),
      ...additionalConfigs,
    ].filter(Boolean),
    includeConfigs: (config, configsToInclude): void => {
      for (const configToInclude of configsToInclude) {
        for (const [key, mergeValues] of strategyEntries) {
          const valueToInclude = readOwnValue(configToInclude, key);

          if (valueToInclude === undefined) {
            continue;
          }

          // eslint-disable-next-line ts/no-unsafe-type-assertion -- Each strategy returns its own key's type
          writeOwnValue(config, key, <TConfig[keyof TConfig]>mergeValues(readOwnValue(config, key), valueToInclude));
        }
      }
    },
  };
};
