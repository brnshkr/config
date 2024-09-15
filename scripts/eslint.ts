import { spawn } from 'node:child_process';

const argv = process.argv.slice(2);

const command = [
  'eslint',
  '--config',
  './conf/eslint.config.ts',
  '--cache',
  '--cache-location',
  './.cache/eslint.cache.json',
  ...argv,
];

spawn('bun', command, {
  stdio: 'inherit',
  env: import.meta.env,
});
