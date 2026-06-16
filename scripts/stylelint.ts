/**
 * @file Thin CLI wrapper that spawns `stylelint` against the bundled
 * `conf/stylelint.config.mjs` with caching enabled. Globs across common style-bearing file
 * extensions and forwards arbitrary CLI arguments through.
 */

import { spawn } from 'node:child_process';

const EXTENSIONS = <const>[
  'css',
  'ejs',
  'html',
  'less',
  'postcss',
  'scss',
  'svelte',
  'svg',
  'vue',
];

const argv = process.argv.slice(2);

const command = [
  'x',
  'stylelint',
  '--config',
  './conf/stylelint.config.mjs',
  '--config-basedir',
  './',
  '--cache',
  '--cache-location',
  './.cache/stylelint.cache.json',
  '--max-warnings',
  '0',
  argv.includes('--print-config') ? '' : `"**/*.{${EXTENSIONS.join(',')}}"`,
  ...argv,
].filter(Boolean);

spawn('bun', command, {
  stdio: 'inherit',
  env: import.meta.env,
});
