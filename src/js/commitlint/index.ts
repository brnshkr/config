import { isModuleEnabledByDefault } from '../shared/utils/module';

import { configs } from './configs';
import { getUserConfigs, includeConfigs } from './utils/config';
import { isModuleEnabled, MODULES, setModuleEnabled } from './utils/module';

import type { Config } from './types/config';
import type { ResolvedOptions, UserOptions } from './types/options';

/**
 * Build the `@brnshkr` commitlint config object.
 *
 * Every preset is optional and enabled only when it is installed, so a project gets the organization's rules
 * whether or not it has the conventional preset.
 *
 * @api
 *
 * @param optionsAndGlobalConfig per-module toggles and global commitlint fields merged with the defaults
 * @param additionalConfigs extra config entries merged after the built-in ones
 *
 * @returns final commitlint config ready to be consumed by commitlint
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/js/commitlint.md
 *
 * @example
 * import { getConfig } from '@brnshkr/config/commitlint';
 *
 * getConfig(undefined);
 * getConfig({});
 *
 * getConfig({
 *   conventional: false,
 *   rules: { 'scope-min-length': [2, 'always', 3] },
 * });
 */
export const getConfig = (
  optionsAndGlobalConfig?: UserOptions,
  ...additionalConfigs: Config[]
): Config => {
  const resolvedOptions = <const>{
    conventional: isModuleEnabledByDefault(MODULES.conventional),
    functions: isModuleEnabledByDefault(MODULES.functions),
    tense: isModuleEnabledByDefault(MODULES.tense),
    ...optionsAndGlobalConfig,
  } satisfies ResolvedOptions;

  setModuleEnabled(MODULES.conventional, resolvedOptions.conventional);
  setModuleEnabled(MODULES.functions, resolvedOptions.functions);
  setModuleEnabled(MODULES.tense, resolvedOptions.tense !== false);

  const config: Config = {};

  includeConfigs(config, configs.commit());

  if (isModuleEnabled(MODULES.conventional)) {
    includeConfigs(config, configs.conventional());
  }

  if (isModuleEnabled(MODULES.functions)) {
    includeConfigs(config, configs.functions());
  }

  if (isModuleEnabled(MODULES.tense)) {
    includeConfigs(config, configs.tense(
      typeof resolvedOptions.tense === 'object'
        ? resolvedOptions.tense
        : undefined,
    ));
  }

  includeConfigs(config, getUserConfigs(resolvedOptions, additionalConfigs));

  return config;
};

/**
 * Default-exported pre-built config for direct re-export from a commitlint config file.
 *
 * @api
 */
// eslint-disable-next-line import/no-default-export -- Explicitly expose this module with a default export to allow for direct re-exporting
export default getConfig();
