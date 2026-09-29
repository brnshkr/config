/**
 * @internal @brnshkr/config/markdownlint
 */

import {
  createConfigMerger,
  merge,
  replace,
  union,
} from '../../shared/utils/config-merger';

import { resolveModulePath } from '../../shared/utils/resolve';

import type { MergeStrategies } from '../../shared/utils/config-merger';
import type { Config } from '../types/config';

// NOTICE: markdownlint would load a rule's CommonJS build, and ESM nests that inside an extra `default` it cannot see
export const resolveCustomRule = (id: string): string => resolveModulePath(id) ?? id;

const MERGE_STRATEGIES = <const>{
  $schema: replace,
  config: merge,
  customRules: union,
  fix: replace,
  frontMatter: replace,
  gitignore: replace,
  globs: union,
  ignores: union,
  markdownItPlugins: replace,
  modulePaths: union,
  noBanner: replace,
  noInlineConfig: replace,
  noProgress: replace,
  outputFormatters: replace,
  overrides: (currentOverrides, overridesToInclude) => [
    ...currentOverrides ?? [],
    ...overridesToInclude.map((override) => ({
      combine: <const>'merge',
      ...override,
    })),
  ],
  showFound: replace,
} satisfies MergeStrategies<Config>;

export const { getUserConfigs, includeConfigs } = createConfigMerger<Config>(MERGE_STRATEGIES);
