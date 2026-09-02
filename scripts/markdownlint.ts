import { spawn } from 'node:child_process';

import { withDefaultGlobs } from './utils/argv';

const VALUE_FLAGS = <const>[
  '--config',
  '--configPointer',
];

const command = [
  'x',
  'markdownlint-cli2',
  '--config',
  './conf/markdownlint.config.mjs',
  ...withDefaultGlobs(process.argv.slice(2), ['**/*.md'], VALUE_FLAGS),
];

const bunProcess = spawn('bun', command, {
  stdio: 'inherit',
  env: import.meta.env,
});

bunProcess.on('close', (code) => {
  process.exitCode = code ?? 1;
});
