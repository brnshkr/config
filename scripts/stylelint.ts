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
  'stylelint',
  '--config',
  './conf/stylelint.config.mjs',
  '--cache',
  '--cache-location',
  './.cache/stylelint.cache.json',
  argv.includes('--print-config') ? '' : `**/*.{${EXTENSIONS.join(',')}}`,
  ...argv,
].filter(Boolean);

spawn('bun', command, {
  stdio: 'inherit',
  env: import.meta.env,
});
