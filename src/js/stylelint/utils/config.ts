import { objectEntries } from '../../shared/utils/object';
import { packageOrganization } from '../../shared/utils/package-json';

import type { Maybe } from '../../shared/types/core';
import type { Config } from '../types/config';
import type { ResolvedOptions } from '../types/options';
import type { Override } from '../types/overrides';

export const buildOverrideName = (
  override: Override,
): string => [packageOrganization, override]
  .filter(Boolean)
  .join('/');

const isValidGlobalAdditionalConfigKey = (
  key: string,
): key is keyof Omit<Config, 'ignorePatterns' | '_processorFunctions'> => (<const>[
  'extends',
  'plugins',
  'pluginFunctions',
  'ignoreFiles',
  'rules',
  'quiet',
  'formatter',
  'defaultSeverity',
  'ignoreDisables',
  'reportNeedlessDisables',
  'reportInvalidScopeDisables',
  'reportDescriptionlessDisables',
  'reportUnscopedDisables',
  'configurationComment',
  'overrides',
  'customSyntax',
  'processors',
  'languageOptions',
  'allowEmptyInput',
  'cache',
  'fix',
  'computeEditInfo',
  'validate',
] satisfies (keyof Omit<Config, 'ignorePatterns' | '_processorFunctions'>)[]).includes(key);

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

  return config;
};

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

    if (configToInclude.plugins !== undefined) {
      config.plugins = [...new Set([
        ...(Array.isArray(config.plugins)
          ? (config.plugins ?? [])
          : [config.plugins].filter(Boolean)),
        ...(Array.isArray(configToInclude.plugins)
          ? (configToInclude.plugins ?? [])
          : [configToInclude.plugins].filter(Boolean)),
      ])];
    }

    if (configToInclude.pluginFunctions !== undefined) {
      config.pluginFunctions = {
        ...config.pluginFunctions,
        ...configToInclude.pluginFunctions,
      };
    }

    if (configToInclude.ignoreFiles !== undefined) {
      config.ignoreFiles = [...new Set([
        ...(Array.isArray(config.ignoreFiles)
          ? (config.ignoreFiles ?? [])
          : [config.ignoreFiles].filter(Boolean)),
        ...(Array.isArray(configToInclude.ignoreFiles)
          ? (configToInclude.ignoreFiles ?? [])
          : [configToInclude.ignoreFiles].filter(Boolean)),
      ])];
    }

    if (configToInclude.rules !== undefined) {
      config.rules = {
        ...config.rules,
        ...configToInclude.rules,
      };
    }

    if (configToInclude.quiet !== undefined) {
      config.quiet = configToInclude.quiet;
    }

    if (configToInclude.formatter !== undefined) {
      config.formatter = configToInclude.formatter;
    }

    if (configToInclude.defaultSeverity !== undefined) {
      config.defaultSeverity = configToInclude.defaultSeverity;
    }

    if (configToInclude.ignoreDisables !== undefined) {
      config.ignoreDisables = configToInclude.ignoreDisables;
    }

    if (configToInclude.reportNeedlessDisables !== undefined) {
      config.reportNeedlessDisables = configToInclude.reportNeedlessDisables;
    }

    if (configToInclude.reportInvalidScopeDisables !== undefined) {
      config.reportInvalidScopeDisables = configToInclude.reportInvalidScopeDisables;
    }

    if (configToInclude.reportDescriptionlessDisables !== undefined) {
      config.reportDescriptionlessDisables = configToInclude.reportDescriptionlessDisables;
    }

    if (configToInclude.reportUnscopedDisables !== undefined) {
      config.reportUnscopedDisables = configToInclude.reportUnscopedDisables;
    }

    if (configToInclude.configurationComment !== undefined) {
      config.configurationComment = configToInclude.configurationComment;
    }

    if (configToInclude.overrides !== undefined) {
      const overridesToIncude = configToInclude.overrides;

      config.overrides = [
        ...(config.overrides ?? []).filter(
          (existingOverride) => !overridesToIncude.some(
            (overrideToIncude) => overrideToIncude.name !== undefined
              && overrideToIncude.name === existingOverride.name,
          ),
        ),
        ...overridesToIncude,
      ];
    }

    if (configToInclude.customSyntax !== undefined) {
      config.customSyntax = configToInclude.customSyntax;
    }

    if (configToInclude.processors !== undefined) {
      config.processors = [...new Set([...(config.processors ?? []), ...configToInclude.processors])];
    }

    if (configToInclude.languageOptions !== undefined) {
      config.languageOptions = {
        ...config.languageOptions,
        ...configToInclude.languageOptions,
        syntax: {
          atRules: {
            ...config.languageOptions?.syntax?.atRules,
            ...configToInclude.languageOptions.syntax?.atRules,
          },
          cssWideKeywords: [...new Set([
            ...config.languageOptions?.syntax?.cssWideKeywords ?? [],
            ...configToInclude.languageOptions.syntax?.cssWideKeywords ?? [],
          ])],
          properties: {
            ...config.languageOptions?.syntax?.properties,
            ...configToInclude.languageOptions.syntax?.properties,
          },
          types: {
            ...config.languageOptions?.syntax?.types,
            ...configToInclude.languageOptions.syntax?.types,
          },
        },
      };
    }

    if (configToInclude.allowEmptyInput !== undefined) {
      config.allowEmptyInput = configToInclude.allowEmptyInput;
    }

    if (configToInclude.cache !== undefined) {
      config.cache = configToInclude.cache;
    }

    if (configToInclude.fix !== undefined) {
      config.fix = configToInclude.fix;
    }

    if (configToInclude.computeEditInfo !== undefined) {
      config.computeEditInfo = configToInclude.computeEditInfo;
    }

    if (configToInclude.validate !== undefined) {
      config.validate = configToInclude.validate;
    }
  }
};
