import { FlatConfigComposer } from 'eslint-flat-config-utils';

import { isModuleEnabledByDefault } from '../shared/utils/module';
import { packageOrganization } from '../shared/utils/package-json';

import { configs } from './configs';
import { getUserConfigs } from './utils/config';
import { isModuleEnabled, MODULES, setModuleEnabled } from './utils/module';

import type { Awaitable } from '../shared/types/core';
import type { Config, ConfigNames, ResolvableConfig } from './types/config';
import type { ResolvedOptions, UserOptions } from './types/options';

export const getConfig = (
  optionsAndGlobalConfig?: UserOptions,
  ...additionalConfigs: Awaitable<Config>[]
// eslint-disable-next-line ts/promise-function-async -- Explicitly mark function as synchronous since the promises are handled by the composer
): FlatConfigComposer<Config, ConfigNames> => {
  const resolvedOptions = <const>{
    [packageOrganization]: isModuleEnabledByDefault(MODULES[packageOrganization]),
    comments: isModuleEnabledByDefault(MODULES.comments),
    css: isModuleEnabledByDefault(MODULES.css),
    gitignore: isModuleEnabledByDefault(MODULES.gitignore),
    import: isModuleEnabledByDefault(MODULES.import),
    jsdoc: isModuleEnabledByDefault(MODULES.jsdoc),
    json: isModuleEnabledByDefault(MODULES.json),
    markdown: isModuleEnabledByDefault(MODULES.markdown),
    node: isModuleEnabledByDefault(MODULES.node),
    perfectionist: isModuleEnabledByDefault(MODULES.perfectionist),
    regexp: isModuleEnabledByDefault(MODULES.regexp),
    style: isModuleEnabledByDefault(MODULES.style),
    svelte: isModuleEnabledByDefault(MODULES.svelte),
    test: isModuleEnabledByDefault(MODULES.test),
    toml: isModuleEnabledByDefault(MODULES.toml),
    typescript: isModuleEnabledByDefault(MODULES.typescript),
    unicorn: isModuleEnabledByDefault(MODULES.unicorn),
    yaml: isModuleEnabledByDefault(MODULES.yaml),
    ...optionsAndGlobalConfig,
  } satisfies ResolvedOptions;

  setModuleEnabled(MODULES[packageOrganization], resolvedOptions[packageOrganization]);
  setModuleEnabled(MODULES.comments, resolvedOptions.comments);
  setModuleEnabled(MODULES.css, resolvedOptions.css);
  setModuleEnabled(MODULES.gitignore, resolvedOptions.gitignore !== false);
  setModuleEnabled(MODULES.import, resolvedOptions.import);
  setModuleEnabled(MODULES.jsdoc, resolvedOptions.jsdoc);
  setModuleEnabled(MODULES.json, resolvedOptions.json);
  setModuleEnabled(MODULES.markdown, resolvedOptions.markdown !== false);
  setModuleEnabled(MODULES.node, resolvedOptions.node !== false);
  setModuleEnabled(MODULES.perfectionist, resolvedOptions.perfectionist);
  setModuleEnabled(MODULES.regexp, resolvedOptions.regexp);
  setModuleEnabled(MODULES.style, resolvedOptions.style);
  setModuleEnabled(MODULES.svelte, resolvedOptions.svelte);
  setModuleEnabled(MODULES.test, resolvedOptions.test);
  setModuleEnabled(MODULES.toml, resolvedOptions.toml);
  setModuleEnabled(MODULES.typescript, resolvedOptions.typescript !== false);
  setModuleEnabled(MODULES.unicorn, resolvedOptions.unicorn);
  setModuleEnabled(MODULES.yaml, resolvedOptions.yaml);

  const composer = new FlatConfigComposer<Config, ConfigNames>();

  const appendToComposer = (...configsToAppend: ResolvableConfig[]): void => {
    // eslint-disable-next-line no-void -- Explicitly mark the promises as ignored with void and let the composer handle them
    void composer.append(...configsToAppend);
  };

  appendToComposer(configs.ignores(resolvedOptions.ignores));

  if (isModuleEnabled(MODULES.gitignore)) {
    appendToComposer(configs.gitignore(
      typeof resolvedOptions.gitignore === 'object'
        ? resolvedOptions.gitignore
        : { strict: false },
    ));
  }

  appendToComposer(configs.javascript());

  if (isModuleEnabled(MODULES.typescript)) {
    appendToComposer(configs.typescript(
      typeof resolvedOptions.typescript === 'object'
        ? resolvedOptions.typescript
        : undefined,
    ));
  }

  if (isModuleEnabled(MODULES.test)) {
    appendToComposer(configs.test());
  }

  if (isModuleEnabled(MODULES.jsdoc)) {
    appendToComposer(configs.jsdoc());
  }

  if (isModuleEnabled(MODULES.node)) {
    appendToComposer(configs.node(
      typeof resolvedOptions.node === 'object'
        ? resolvedOptions.node
        : undefined,
    ));
  }

  if (isModuleEnabled(MODULES[packageOrganization])) {
    appendToComposer(configs[packageOrganization]());
  }

  if (isModuleEnabled(MODULES.comments)) {
    appendToComposer(configs.comments());
  }

  if (isModuleEnabled(MODULES.regexp)) {
    appendToComposer(configs.regexp());
  }

  if (isModuleEnabled(MODULES.unicorn)) {
    appendToComposer(configs.unicorn());
  }

  if (isModuleEnabled(MODULES.import)) {
    appendToComposer(configs.import());
  }

  if (isModuleEnabled(MODULES.style)) {
    appendToComposer(configs.style());
  }

  if (isModuleEnabled(MODULES.perfectionist)) {
    appendToComposer(configs.perfectionist());
  }

  if (isModuleEnabled(MODULES.svelte)) {
    appendToComposer(configs.svelte());
  }

  if (isModuleEnabled(MODULES.json)) {
    appendToComposer(configs.json());
  }

  if (isModuleEnabled(MODULES.toml)) {
    appendToComposer(configs.toml());
  }

  if (isModuleEnabled(MODULES.yaml)) {
    appendToComposer(configs.yaml());
  }

  if (isModuleEnabled(MODULES.css)) {
    appendToComposer(configs.css());
  }

  if (isModuleEnabled(MODULES.markdown)) {
    appendToComposer(configs.markdown(
      typeof resolvedOptions.markdown === 'object'
        ? resolvedOptions.markdown
        : undefined,
    ));
  }

  appendToComposer(configs.overrides());
  appendToComposer(...getUserConfigs(resolvedOptions, additionalConfigs));

  return composer;
};

const getDefaultConfig = async (): Promise<FlatConfigComposer<Config, ConfigNames>> => getConfig();

// NOTICE: Wrap async default export in a function to ensure eslint can resolve it in all environments
// eslint-disable-next-line import/no-default-export -- Explicitly expose this module with a default export to allow for direct re-exporting from eslint config file
export default getDefaultConfig;
