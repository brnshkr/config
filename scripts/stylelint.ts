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

const bunProcess = spawn('bun', command, {
  stdio: 'inherit',
  env: import.meta.env,
});

bunProcess.on('close', (code) => {
  process.exitCode = code ?? 1;
});
