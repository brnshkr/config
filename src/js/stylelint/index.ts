import { isModuleEnabledByDefault } from '#shared/utils/module.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';
import { configs } from '#stylelint/configs/index.ts';
import { getUserConfigs, includeConfigs } from '#stylelint/utils/config.ts';

import {
  BUILTIN_MODULE,
  isModuleEnabled,
  MODULES,
  setModuleEnabled,
} from '#stylelint/utils/module.ts';

import type { Config } from '#stylelint/types/config.ts';
import type { ResolvedOptions, UserOptions } from '#stylelint/types/options.ts';

/**
 * Build the `@brnshkr` Stylelint config object.
 *
 * Merges sensible defaults for every supported module (SCSS, Less, HTML, etc.) with user
 * overrides and any additional configs.
 *
 * @api
 *
 * @param optionsAndGlobalConfig - per-module toggles and global Stylelint fields merged with the defaults
 * @param additionalConfigs - extra Stylelint config entries merged after the built-in ones
 *
 * @returns final Stylelint config ready to be consumed by Stylelint
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/js/stylelint/index.md
 *
 * @example
 * ```js
 * import { getConfig } from '@brnshkr/config/stylelint';
 *
 * getConfig(undefined);
 * getConfig({});
 *
 * getConfig({
 *   scss: false,
 *   ignoreFiles: ['dist/**'],
 * }, {
 *   rules: { 'color-no-hex': null },
 * });
 *
 * getConfig(undefined, {
 *   rules: { 'color-no-hex': null },
 * });
 * ```
 */
export const getConfig = (
  optionsAndGlobalConfig?: UserOptions,
  ...additionalConfigs: Config[]
): Config => {
  const resolvedOptions = <const>{
    [packageOrganization]: isModuleEnabledByDefault(BUILTIN_MODULE),
    baseline: isModuleEnabledByDefault(MODULES.baseline),
    defensive: isModuleEnabledByDefault(MODULES.defensive),
    dialect: {},
    html: isModuleEnabledByDefault(MODULES.html),
    less: isModuleEnabledByDefault(MODULES.less),
    markdown: isModuleEnabledByDefault(MODULES.markdown),
    modules: isModuleEnabledByDefault(MODULES.modules),
    nesting: isModuleEnabledByDefault(MODULES.nesting),
    order: isModuleEnabledByDefault(MODULES.order),
    scss: isModuleEnabledByDefault(MODULES.scss),
    strict: isModuleEnabledByDefault(MODULES.strict),
    style: isModuleEnabledByDefault(MODULES.style),
    tailwind: isModuleEnabledByDefault(MODULES.tailwind),
    ...optionsAndGlobalConfig,
  } satisfies ResolvedOptions;

  // eslint-disable-next-line security/detect-object-injection -- The key is the package's own organization name
  setModuleEnabled(BUILTIN_MODULE, resolvedOptions[packageOrganization]);
  setModuleEnabled(MODULES.baseline, resolvedOptions.baseline);
  setModuleEnabled(MODULES.defensive, resolvedOptions.defensive);
  setModuleEnabled(MODULES.html, resolvedOptions.html);
  setModuleEnabled(MODULES.less, resolvedOptions.less);
  setModuleEnabled(MODULES.markdown, resolvedOptions.markdown);
  setModuleEnabled(MODULES.modules, resolvedOptions.modules);
  setModuleEnabled(MODULES.nesting, resolvedOptions.nesting);
  setModuleEnabled(MODULES.order, resolvedOptions.order);
  setModuleEnabled(MODULES.scss, resolvedOptions.scss);
  setModuleEnabled(MODULES.strict, resolvedOptions.strict);
  setModuleEnabled(MODULES.style, resolvedOptions.style);
  setModuleEnabled(MODULES.tailwind, resolvedOptions.tailwind);

  const config: Config = {};

  includeConfigs(config, configs.ignores());
  includeConfigs(config, configs.css(resolvedOptions.dialect));

  if (isModuleEnabled(MODULES.baseline)) {
    includeConfigs(config, configs.baseline());
  }

  if (isModuleEnabled(MODULES.defensive)) {
    includeConfigs(config, configs.defensive());
  }

  if (isModuleEnabled(MODULES.nesting)) {
    includeConfigs(config, configs.nesting());
  }

  if (isModuleEnabled(MODULES.order)) {
    includeConfigs(config, configs.order());
  }

  if (isModuleEnabled(MODULES.less)) {
    includeConfigs(config, configs.less());
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

  if (isModuleEnabled(MODULES.tailwind)) {
    includeConfigs(config, configs.tailwind());
  }

  if (isModuleEnabled(BUILTIN_MODULE)) {
    // eslint-disable-next-line security/detect-object-injection -- The key is the package's own organization name
    includeConfigs(config, configs[packageOrganization]());
  }

  if (isModuleEnabled(BUILTIN_MODULE) && isModuleEnabled(MODULES.markdown)) {
    includeConfigs(config, configs.markdown(config));
  }

  includeConfigs(config, getUserConfigs(resolvedOptions, additionalConfigs));

  return config;
};

/**
 * Default-exported pre-built config for direct re-export from a Stylelint config file.
 *
 * @api
 */
// eslint-disable-next-line import/no-default-export -- Explicitly expose this module with a default export to allow for direct re-exporting
export default getConfig();
