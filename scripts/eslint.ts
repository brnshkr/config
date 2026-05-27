/**
 * @file Thin CLI wrapper that spawns `eslint` against the bundled `conf/eslint.config.ts` with
 * caching enabled. Forwards arbitrary CLI arguments through to ESLint.
 */

import { spawn } from 'node:child_process';

const argv = process.argv.slice(2);

const command = [
  'eslint',
  '--config',
  './conf/eslint.config.ts',
  '--cache',
  '--cache-location',
  './.cache/eslint.cache.json',
  '--max-warnings',
  '0',
  ...argv,
];

spawn('bun', command, {
  stdio: 'inherit',
  env: import.meta.env,
});
