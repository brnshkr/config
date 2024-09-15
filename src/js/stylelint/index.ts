import { isModuleEnabledByDefault } from '../shared/utils/module';

import { configs } from './configs';
import { getUserConfigs, includeConfigs } from './utils/config';
import { isModuleEnabled, MODULES, setModuleEnabled } from './utils/module';

import type { Config } from './types/config';
import type { ResolvedOptions, UserOptions } from './types/options';

export const getConfig = (
  optionsAndGlobalConfig?: UserOptions,
  ...additionalConfigs: Config[]
): Config => {
  const resolvedOptions = <const>{
    baseline: isModuleEnabledByDefault(MODULES.baseline),
    defensive: isModuleEnabledByDefault(MODULES.defensive),
    html: isModuleEnabledByDefault(MODULES.html),
    logical: isModuleEnabledByDefault(MODULES.logical),
    modules: isModuleEnabledByDefault(MODULES.modules),
    nesting: isModuleEnabledByDefault(MODULES.nesting),
    order: isModuleEnabledByDefault(MODULES.order),
    scss: isModuleEnabledByDefault(MODULES.scss),
    strict: isModuleEnabledByDefault(MODULES.strict),
    style: isModuleEnabledByDefault(MODULES.style),
    ...optionsAndGlobalConfig,
  } satisfies ResolvedOptions;

  setModuleEnabled(MODULES.baseline, resolvedOptions.baseline);
  setModuleEnabled(MODULES.defensive, resolvedOptions.defensive);
  setModuleEnabled(MODULES.html, resolvedOptions.html);
  setModuleEnabled(MODULES.logical, resolvedOptions.logical);
  setModuleEnabled(MODULES.modules, resolvedOptions.modules);
  setModuleEnabled(MODULES.nesting, resolvedOptions.nesting);
  setModuleEnabled(MODULES.order, resolvedOptions.order);
  setModuleEnabled(MODULES.scss, resolvedOptions.scss);
  setModuleEnabled(MODULES.strict, resolvedOptions.strict);
  setModuleEnabled(MODULES.style, resolvedOptions.style);

  const config: Config = {};

  includeConfigs(config, configs.ignores());
  includeConfigs(config, configs.css());

  if (isModuleEnabled(MODULES.baseline)) {
    includeConfigs(config, configs.baseline());
  }

  if (isModuleEnabled(MODULES.defensive)) {
    includeConfigs(config, configs.defensive());
  }

  if (isModuleEnabled(MODULES.logical)) {
    includeConfigs(config, configs.logical());
  }

  if (isModuleEnabled(MODULES.nesting)) {
    includeConfigs(config, configs.nesting());
  }

  if (isModuleEnabled(MODULES.order)) {
    includeConfigs(config, configs.order());
  }

  if (isModuleEnabled(MODULES.scss)) {
    includeConfigs(config, configs.scss());
  }

  if (isModuleEnabled(MODULES.html)) {
    includeConfigs(config, configs.html());
  }

  if (isModuleEnabled(MODULES.modules)) {
    includeConfigs(config, configs.modules());
  }

  if (isModuleEnabled(MODULES.strict)) {
    includeConfigs(config, configs.strict());
  }

  if (isModuleEnabled(MODULES.style)) {
    includeConfigs(config, configs.style());
  }

  includeConfigs(config, getUserConfigs(resolvedOptions, additionalConfigs));

  return config;
};

// eslint-disable-next-line import/no-default-export -- Explicitly expose this module with a default export to allow for direct re-exporting from eslint config file
export default getConfig();
