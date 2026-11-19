/**
 * @internal @brnshkr/config/stylelint
 */

import {
  createConfigMerger,
  merge,
  replace,
  union,
} from '#shared/utils/config-merger.ts';

import { packageOrganization } from '#shared/utils/package-json.ts';

import type { MergeStrategies } from '#shared/utils/config-merger.ts';
import type { Config } from '#stylelint/types/config.ts';
import type { Override } from '#stylelint/types/overrides.ts';

type MergedConfig = Omit<Config, 'computeEditInfo' | 'ignorePatterns' | `_${string}`>;

export const buildOverrideName = (
  override: Override,
): string => [packageOrganization, override]
  .filter(Boolean)
  .join('/');

const MERGE_STRATEGIES = <const>{
  extends: union,
  plugins: union,
  ignoreFiles: union,
  rules: merge,
  quiet: replace,
  formatter: replace,
  defaultSeverity: replace,
  ignoreDisables: replace,
  reportNeedlessDisables: replace,
  reportInvalidScopeDisables: replace,
  reportDescriptionlessDisables: replace,
  reportUnscopedDisables: replace,
  maxWarnings: replace,
  configurationComment: replace,
  overrides: (currentOverrides, overridesToInclude) => [
    ...(currentOverrides ?? []).filter(
      (existingOverride) => overridesToInclude.every(
        (overrideToInclude) => overrideToInclude.name === undefined
          || overrideToInclude.name !== existingOverride.name,
      ),
    ),
    ...overridesToInclude,
  ],
  customSyntax: replace,
  processors: union,
  referenceFiles: union,
  languageOptions: (currentLanguageOptions, languageOptionsToInclude) => {
    const currentSyntax = currentLanguageOptions?.syntax ?? {};
    const syntaxToInclude = languageOptionsToInclude.syntax ?? {};

    return {
      ...currentLanguageOptions,
      ...languageOptionsToInclude,
      syntax: {
        atRules: merge(currentSyntax.atRules, syntaxToInclude.atRules ?? {}),
        cssWideKeywords: union(currentSyntax.cssWideKeywords, syntaxToInclude.cssWideKeywords ?? []),
        properties: merge(currentSyntax.properties, syntaxToInclude.properties ?? {}),
        types: merge(currentSyntax.types, syntaxToInclude.types ?? {}),
      },
    };
  },
  allowEmptyInput: replace,
  cache: replace,
  fix: replace,
  validate: replace,
} satisfies MergeStrategies<MergedConfig>;

export const { getUserConfigs, includeConfigs } = createConfigMerger<MergedConfig>(MERGE_STRATEGIES);
