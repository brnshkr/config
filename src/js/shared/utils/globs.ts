/**
 * @internal @brnshkr/config
 */

import path from 'node:path';

import { getEnvironmentValue } from './environment';

const getDirectoryGlob = (variableName: string, defaultDirectory: string): string => {
  const configuredDirectory = getEnvironmentValue(variableName) ?? '';

  return `**/${path.normalize(configuredDirectory === '' ? defaultDirectory : configuredDirectory)}/**`;
};

export const GLOB_TEST_FILES = <const>[
  '**/__tests__/**/*.?(c|m)[jt]s?(x)',
  '**/*.spec.?(c|m)[jt]s?(x)',
  '**/*.test.?(c|m)[jt]s?(x)',
] satisfies string[];

export const GLOB_BENCHMARK_FILES = <const>[
  '**/*.bench.?(c|m)[jt]s?(x)',
  '**/*.benchmark.?(c|m)[jt]s?(x)',
] satisfies string[];

export const GLOB_IGNORES = <const>[
  getDirectoryGlob('BRNSHKR_CACHE_DIR', '.cache'),
  '**/.changeset/**',
  '**/.git/objects/**',
  '**/.git/subtree-cache/**',
  '**/.hg/store/**',
  '**/.history/**',
  '**/.idea/**',
  getDirectoryGlob('BRNSHKR_LOCAL_DIR', '.local'),
  '**/.next/**',
  '**/.nuxt/**',
  '**/.output/**',
  '**/.svelte-kit/**',
  '**/.temp/**',
  '**/.tmp/**',
  '**/.vercel/**',
  '**/.vite-inspect/**',
  '**/.vitepress/cache/**',
  '**/.vitest/**',
  '**/.yarn/**',
  '**/__generated__/**',
  '**/__snapshots__/**',
  '**/*.code-search',
  '**/*.example',
  '**/*.min.*',
  '**/bower_components/**',
  '**/bun.lock',
  '**/composer.lock',
  '**/coverage/**',
  '**/dist/**',
  '**/node_modules/**',
  '**/output/**',
  '**/package-lock.json',
  '**/pnpm-lock.yaml',
  '**/temp/**',
  '**/tests/**/[Ff]ixture?(s)/**',
  '**/tmp/**',
  '**/vendor/**',
  '**/vite.config.*.timestamp-*',
  '**/yarn.lock',
] satisfies string[];
