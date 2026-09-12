/**
 * @internal @brnshkr/config/eslint
 */

import { GLOB_BENCHMARK_FILES, GLOB_TEST_FILES as GLOB_TEST_FILES_SHARED } from '../../shared/utils/globs';

export const GLOB_CJS = '**/*.cjs';
export const GLOB_TS = '**/*.?(c|m)ts?(x)';
export const GLOB_DTS = '**/*.d.?(c|m)ts';
export const GLOB_SVELTE = '**/*.svelte';
export const GLOB_SVELTE_SCRIPT = '**/*.svelte.[jt]s';
export const GLOB_JSON = '**/*.json';
export const GLOB_JSONC = '**/*.jsonc';
export const GLOB_JSON5 = '**/*.json5';
export const GLOB_YAML = '**/*.y?(a)ml';
export const GLOB_TOML = '**/*.toml';
export const GLOB_CSS = '**/*.css';
export const GLOB_MD = '**/*.md';
export const GLOB_EXAMPLES = '**/*.md/*.js';

export const GLOB_SCRIPT_FILES = <const>[
  '**/*.?(c|m)[jt]s?(x)',
  GLOB_DTS,
  GLOB_SVELTE,
  GLOB_SVELTE_SCRIPT,
] satisfies string[];

export const GLOB_SCRIPT_FILES_WITHOUT_TS = <const>[
  '**/*.?(c|m)js?(x)',
  GLOB_DTS,
] satisfies string[];

export const GLOB_DEVELOPMENT_FILES = <const>[
  '**/*.config.?(c|m)[jt]s',
  '**/{conf,tests}/**',
  '**/types/declarations/reset.d.ts',
] satisfies string[];

export const GLOB_YAML_FIXED_EXTENSION_FILES = <const>[
  '**/.circleci/**/*.yml',
  '**/.github/**/*.yml',
  '**/.gitlab-ci.yml',
  '**/.gitlab/**/*.yml',
  '**/.travis.yml',
  '**/appveyor.yml',
] satisfies string[];

export const GLOB_TEST_FILES = <const>[
  ...GLOB_TEST_FILES_SHARED,
  ...GLOB_BENCHMARK_FILES,
] satisfies string[];
