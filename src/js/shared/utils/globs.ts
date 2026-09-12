/**
 * @internal @brnshkr/config
 */

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
  '**/.cache/**',
  '**/.changeset/**',
  '**/.git/objects/**',
  '**/.git/subtree-cache/**',
  '**/.hg/store/**',
  '**/.history/**',
  '**/.idea/**',
  '**/.local/**',
  '**/.next/**',
  '**/.nuxt/**',
  '**/.output/**',
  '**/.svelte-kit/**',
  '**/.temp/**',
  '**/.tmp/**',
  '**/.vercel/**',
  '**/.vite-inspect/**',
  '**/.vitepress/cache/**',
  '**/.yarn/**',
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
