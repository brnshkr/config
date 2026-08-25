import { spawn } from 'node:child_process';

const argv = process.argv.slice(2);

const command = [
  'x',
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

const bunProcess = spawn('bun', command, {
  stdio: 'inherit',
  env: import.meta.env,
});

bunProcess.on('close', (code) => {
  process.exitCode = code ?? 1;
});
