import { spawn } from 'node:child_process';

import { withDefaultGlobs } from './utils/argv';

const VALUE_FLAGS = <const>[
  '--cache-file',
  '--cache-location',
  '--cache-strategy',
  '--concurrency',
  '--config',
  '--ext',
  '--flag',
  '--format',
  '--global',
  '--ignore-pattern',
  '--max-warnings',
  '--output-file',
  '--parser',
  '--plugin',
  '--print-config',
  '--report-unused-disable-directives-severity',
  '--report-unused-inline-configs',
  '--stdin-filename',
  '--suppress-rule',
  '--suppressions-location',
  '-c',
  '-f',
  '-o',
];

const command = [
  'x',
  'eslint',
  '--config',
  './conf/eslint.ts',
  '--cache',
  '--cache-location',
  './.cache/eslint.cache.json',
  '--max-warnings',
  '0',
  ...withDefaultGlobs(process.argv.slice(2), [], VALUE_FLAGS),
];

const bunProcess = spawn('bun', command, {
  stdio: 'inherit',
  env: import.meta.env,
});

bunProcess.on('close', (code) => {
  process.exitCode = code ?? 1;
});
