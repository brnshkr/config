import { isModuleEnabledByDefault } from '../shared/utils/module';

import { configs } from './configs';
import { getUserConfigs, includeConfigs } from './utils/config';
import { isModuleEnabled, MODULES, setModuleEnabled } from './utils/module';

import type { Config } from './types/config';
import type { ResolvedOptions, UserOptions } from './types/options';

/**
 * Build the `@brnshkr` Vitest config object.
 *
 * Collects the project's own tests and the spelling test this package ships, so a repository
 * needs no test file of its own for it, and activates each module lazily, only when its
 * optional peer dependency is installed.
 *
 * @api
 *
 * @param optionsAndGlobalConfig per-module toggles and global Vitest fields merged with the defaults
 * @param additionalConfigs extra config entries merged after the built-in ones
 *
 * @returns final config ready to be consumed by Vitest
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/js/vitest.md
 *
 * @example
 * import { getConfig } from '@brnshkr/config/vitest';
 *
 * getConfig(undefined);
 * getConfig({});
 *
 * getConfig({
 *   paths: false,
 *   test: { testTimeout: 120_000 },
 * });
 */
export const getConfig = (
  optionsAndGlobalConfig?: UserOptions,
  ...additionalConfigs: Config[]
): Config => {
  const resolvedOptions = <const>{
    environment: isModuleEnabledByDefault(MODULES.environment),
    paths: isModuleEnabledByDefault(MODULES.paths),
    spelling: isModuleEnabledByDefault(MODULES.spelling),
    ui: isModuleEnabledByDefault(MODULES.ui),
    ...optionsAndGlobalConfig,
  } satisfies ResolvedOptions;

  setModuleEnabled(MODULES.environment, resolvedOptions.environment);
  setModuleEnabled(MODULES.paths, resolvedOptions.paths);
  setModuleEnabled(MODULES.spelling, resolvedOptions.spelling);
  setModuleEnabled(MODULES.ui, resolvedOptions.ui);

  const config: Config = {};

  includeConfigs(config, configs.test());

  if (isModuleEnabled(MODULES.environment)) {
    includeConfigs(config, configs.environment());
  }

  if (isModuleEnabled(MODULES.paths)) {
    includeConfigs(config, configs.paths());
  }

  if (isModuleEnabled(MODULES.spelling)) {
    includeConfigs(config, configs.spelling());
  }

  if (isModuleEnabled(MODULES.ui)) {
    includeConfigs(config, configs.ui());
  }

  includeConfigs(config, getUserConfigs(resolvedOptions, additionalConfigs));

  return config;
};

/**
 * Default-exported pre-built config for direct re-export from a Vitest config file.
 *
 * @api
 */
// eslint-disable-next-line import/no-default-export -- Explicitly expose this module with a default export to allow for direct re-exporting
export default getConfig();
