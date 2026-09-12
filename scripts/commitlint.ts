import { file } from 'bun';
import { spawn } from 'node:child_process';

const argv = process.argv.slice(2);
const source = await file('./.git/COMMIT_EDITMSG').exists() ? '--edit' : '--last';

const command = [
  'x',
  'commitlint',
  '--config',
  './conf/commitlint.mjs',
  ...argv.length > 0 ? argv : [source],
];

const bunProcess = spawn('bun', command, {
  stdio: 'inherit',
  env: import.meta.env,
});

bunProcess.on('close', (code) => {
  process.exitCode = code ?? 1;
});
