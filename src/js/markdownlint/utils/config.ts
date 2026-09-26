/**
 * @internal @brnshkr/config/markdownlint
 */

import { pickKeys } from '../../shared/utils/object';
import { resolveModulePath } from '../../shared/utils/resolve';

import type { Maybe } from '../../shared/types/core';
import type { Config } from '../types/config';
import type { ResolvedOptions } from '../types/options';

// NOTICE: markdownlint would load a rule's CommonJS build, and ESM nests that inside an extra `default` it cannot see
export const resolveCustomRule = (id: string): string => resolveModulePath(id) ?? id;

const GLOBAL_ADDITIONAL_CONFIG_KEYS = <const>[
  '$schema',
  'config',
  'customRules',
  'fix',
  'frontMatter',
  'gitignore',
  'globs',
  'ignores',
  'markdownItPlugins',
  'modulePaths',
  'noBanner',
  'noInlineConfig',
  'noProgress',
  'outputFormatters',
  'overrides',
  'showFound',
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

// eslint-disable-next-line complexity, max-lines-per-function, max-statements -- Extracting these assignments to separate functions would not improve readability
export const includeConfigs = (config: Config, configsToInclude: Config[]): void => {
  for (const configToInclude of configsToInclude) {
    if (configToInclude.$schema !== undefined) {
      config.$schema = configToInclude.$schema;
    }

    if (configToInclude.config !== undefined) {
      config.config = {
        ...config.config,
        ...configToInclude.config,
      };
    }

    if (configToInclude.customRules !== undefined) {
      config.customRules = [...new Set([
        ...config.customRules ?? [],
        ...configToInclude.customRules,
      ])];
    }

    if (configToInclude.fix !== undefined) {
      config.fix = configToInclude.fix;
    }

    if (configToInclude.frontMatter !== undefined) {
      config.frontMatter = configToInclude.frontMatter;
    }

    if (configToInclude.gitignore !== undefined) {
      config.gitignore = configToInclude.gitignore;
    }

    if (configToInclude.globs !== undefined) {
      config.globs = [...new Set([
        ...config.globs ?? [],
        ...configToInclude.globs,
      ])];
    }

    if (configToInclude.ignores !== undefined) {
      config.ignores = [...new Set([
        ...config.ignores ?? [],
        ...configToInclude.ignores,
      ])];
    }

    if (configToInclude.markdownItPlugins !== undefined) {
      config.markdownItPlugins = configToInclude.markdownItPlugins;
    }

    if (configToInclude.modulePaths !== undefined) {
      config.modulePaths = [...new Set([
        ...config.modulePaths ?? [],
        ...configToInclude.modulePaths,
      ])];
    }

    if (configToInclude.noBanner !== undefined) {
      config.noBanner = configToInclude.noBanner;
    }

    if (configToInclude.noInlineConfig !== undefined) {
      config.noInlineConfig = configToInclude.noInlineConfig;
    }

    if (configToInclude.noProgress !== undefined) {
      config.noProgress = configToInclude.noProgress;
    }

    if (configToInclude.outputFormatters !== undefined) {
      config.outputFormatters = configToInclude.outputFormatters;
    }

    if (configToInclude.overrides !== undefined) {
      config.overrides = [
        ...config.overrides ?? [],
        ...configToInclude.overrides.map((override) => ({
          combine: <const>'merge',
          ...override,
        })),
      ];
    }

    if (configToInclude.showFound !== undefined) {
      config.showFound = configToInclude.showFound;
    }
  }
};
