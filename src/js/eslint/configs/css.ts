import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { GLOB_CSS } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const css = async (): Promise<Config[]> => {
  const {
    requiredAll: [pluginCss],
  } = await resolvePackages(MODULES.css);

  if (!pluginCss) {
    return [];
  }

  return [
    {
      name: buildConfigName(MAIN_SCOPES.CSS, SUB_SCOPES.SETUP),
      plugins: {
        css: pluginCss,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.CSS, SUB_SCOPES.RULES),
      files: [GLOB_CSS],
      language: 'css/css',
      rules: {
        ...pluginCss.configs.recommended.rules,
        'css/prefer-logical-properties': 'error',
        'css/relative-font-units': ['error', {
          allowUnits: ['em', 'rem'],
        }],
        'css/use-baseline': 'error',
      },
    },
  ];
};
