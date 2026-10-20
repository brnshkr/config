/**
 * @internal @brnshkr/config/eslint
 */

import { MAIN_SCOPES, SUB_SCOPES } from '#eslint/types/scopes.ts';
import { buildConfigName, renameRules } from '#eslint/utils/config.ts';
import { GLOB_CSS, GLOB_SCRIPT_FILES } from '#eslint/utils/globs.ts';
import { isModuleEnabled, MODULES, resolvePackages } from '#eslint/utils/module.ts';
import { doesTsConfigExist, resolveTsConfigPath } from '#eslint/utils/tsconfig.ts';
import { INDENT, MAX_LEN } from '#shared/utils/constants.ts';
import { GLOB_HTML } from '#shared/utils/globs.ts';
import { isPlainObject, objectEntries } from '#shared/utils/object.ts';

import type { Config } from '#eslint/types/config.ts';
import type { TailwindOptions, TypescriptOptions } from '#eslint/types/options.ts';

export const tailwind = async (
  options?: Partial<TailwindOptions>,
  typescriptOptions?: boolean | Partial<TypescriptOptions>,
): Promise<Config[]> => {
  const {
    requiredAll: [isTailwindInstalled],
    optional: [pluginTailwind, tailwindColors],
  } = await resolvePackages(MODULES.tailwind);

  if (!isTailwindInstalled || !pluginTailwind) {
    return [];
  }

  const tsConfigPath = resolveTsConfigPath(typeof typescriptOptions === 'object' ? typescriptOptions : undefined);

  const paletteNames = objectEntries(tailwindColors ?? {})
    .filter(([, shades]) => isPlainObject(shades))
    .map(([paletteName]) => paletteName);

  return [
    {
      name: buildConfigName(MAIN_SCOPES.TAILWIND, SUB_SCOPES.SETUP),
      plugins: {
        tailwind: pluginTailwind,
      },
      settings: {
        'better-tailwindcss': {
          detectComponentClasses: true,
          rootFontSize: 16,
          ...((isModuleEnabled(MODULES.typescript) && doesTsConfigExist(tsConfigPath))
            ? {
              tsconfig: tsConfigPath,
            }
            : undefined),
          ...options,
        },
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.TAILWIND, SUB_SCOPES.RULES),
      files: [
        GLOB_CSS,
        ...GLOB_SCRIPT_FILES,
        ...(isModuleEnabled(MODULES.html) ? [GLOB_HTML] : []),
      ],
      rules: {
        ...renameRules(pluginTailwind.configs['recommended-error'].rules, { 'better-tailwindcss': 'tailwind' }),
        'tailwind/enforce-consistent-important-position': 'error',
        'tailwind/enforce-consistent-line-wrapping': ['error', {
          indent: INDENT,
          printWidth: MAX_LEN,
        }],
        'tailwind/enforce-consistent-variable-syntax': 'error',
        'tailwind/enforce-consistent-variant-order': 'error',
        'tailwind/enforce-logical-properties': 'error',
        'tailwind/no-restricted-classes': ['error', {
          restrict: [
            {
              message: 'Avoid the important modifier, raise specificity through cascade layers instead.',
              pattern: '(?:^|:)!|!$',
            },
            ...(paletteNames.length > 0
              ? [
                {
                  message: 'Avoid palette colors, use a theme color instead.',
                  pattern: String.raw`-(?:${paletteNames.join('|')})-\d+(?:/.+)?$`,
                },
              ]
              : []),
          ],
        }],
      },
    },
  ];
};
