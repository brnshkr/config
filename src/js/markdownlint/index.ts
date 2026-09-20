import { isModuleEnabledByDefault } from '../shared/utils/module';

import { configs } from './configs';
import { getUserConfigs, includeConfigs } from './utils/config';
import { isModuleEnabled, MODULES, setModuleEnabled } from './utils/module';

import type { Config } from './types/config';
import type { ResolvedOptions, UserOptions } from './types/options';

/**
 * Build the `@brnshkr` markdownlint config object.
 *
 * The result is the shape `markdownlint-cli2` reads rather than the plain rule set, because `ignores` and
 * `globs` exist only at that level — which is why a consumer needs a config file of its own instead of
 * extending a shared rule set.
 *
 * @api
 *
 * @param optionsAndGlobalConfig per-module toggles and global markdownlint-cli2 fields merged with the defaults
 * @param additionalConfigs extra config entries merged after the built-in ones
 *
 * @returns final config ready to be consumed by markdownlint-cli2
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/js/markdownlint.md
 *
 * @example
 * ```js
 * import { getConfig } from '@brnshkr/config/markdownlint';
 *
 * getConfig(undefined);
 * getConfig({});
 *
 * getConfig({
 *   links: false,
 *   ignores: ['CHANGELOG.md'],
 * }, {
 *   config: { 'no-bare-urls': false },
 * });
 * ```
 */
export const getConfig = (
  optionsAndGlobalConfig?: UserOptions,
  ...additionalConfigs: Config[]
): Config => {
  const resolvedOptions = <const>{
    github: isModuleEnabledByDefault(MODULES.github),
    links: isModuleEnabledByDefault(MODULES.links),
    search: isModuleEnabledByDefault(MODULES.search),
    style: isModuleEnabledByDefault(MODULES.style),
    tables: isModuleEnabledByDefault(MODULES.tables),
    ...optionsAndGlobalConfig,
  } satisfies ResolvedOptions;

  setModuleEnabled(MODULES.github, resolvedOptions.github);
  setModuleEnabled(MODULES.links, resolvedOptions.links);
  setModuleEnabled(MODULES.search, resolvedOptions.search);
  setModuleEnabled(MODULES.style, resolvedOptions.style);
  setModuleEnabled(MODULES.tables, resolvedOptions.tables);

  const config: Config = {};

  includeConfigs(config, configs.ignores());

  if (isModuleEnabled(MODULES.github)) {
    includeConfigs(config, configs.github());
  }

  if (isModuleEnabled(MODULES.links)) {
    includeConfigs(config, configs.links());
  }

  if (isModuleEnabled(MODULES.search)) {
    includeConfigs(config, configs.search());
  }

  if (isModuleEnabled(MODULES.style)) {
    includeConfigs(config, configs.style());
  }

  if (isModuleEnabled(MODULES.tables)) {
    includeConfigs(config, configs.tables());
  }

  includeConfigs(config, configs.markdown());
  includeConfigs(config, getUserConfigs(resolvedOptions, additionalConfigs));

  return config;
};

/**
 * Default-exported pre-built config for direct re-export from a Markdownlint config file.
 *
 * @api
 */
// eslint-disable-next-line import/no-default-export -- Explicitly expose this module with a default export to allow for direct re-exporting
export default getConfig();
