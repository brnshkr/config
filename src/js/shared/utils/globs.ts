/**
 * @internal @brnshkr/config
 */

import path from 'node:path';

import { getCacheDirectory, getLocalDirectory } from '#shared/utils/project-directory.ts';

const getDirectoryGlob = (directory: string): string => `**/${path.normalize(directory)}/**`;

export const GLOB_JS_TS = '**/*.?(c|m)[jt]s?(x)';
export const GLOB_HTML = '**/*.htm?(l)';
export const GLOB_HTML_PHP = `${GLOB_HTML}.php`;
export const GLOB_BLADE = '**/*.blade.php';
export const GLOB_PHTML = '**/*.phtml';
export const GLOB_PHP = '**/*.php';

export const MARKDOWN_EXTENSIONS = <const>[
  'md',
  'markdown',
] satisfies string[];

export const GLOB_MD = `**/*.{${MARKDOWN_EXTENSIONS.join(',')}}`;

export const GLOB_PHP_TEMPLATE_FILES = <const>[
  GLOB_HTML_PHP,
  GLOB_PHTML,
  GLOB_BLADE,
] satisfies string[];

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
  getDirectoryGlob(getCacheDirectory()),
  '**/.changeset/**',
  '**/.git/objects/**',
  '**/.git/subtree-cache/**',
  '**/.hg/store/**',
  '**/.history/**',
  '**/.idea/**',
  getDirectoryGlob(getLocalDirectory()),
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
  '**/build/**',
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
