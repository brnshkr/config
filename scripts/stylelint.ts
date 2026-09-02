import { spawn } from 'node:child_process';

import { withDefaultGlobs } from './utils/argv';

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

const VALUE_FLAGS = <const>[
  '--cache-location',
  '--cache-strategy',
  '--config-basedir',
  '--config',
  '--custom-formatter',
  '--custom-syntax',
  '--formatter',
  '--globby-options',
  '--go',
  '--ignore-path',
  '--ignore-pattern',
  '--ip',
  '--max-warnings',
  '--mw',
  '--output-file',
  '--stdin-filename',
  '--suppress-location',
  '-c',
  '-f',
  '-i',
  '-o',
];

const argv = process.argv.slice(2);

const defaultGlobs = argv.includes('--print-config')
  ? []
  : [`"**/*.{${EXTENSIONS.join(',')}}"`];

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
  ...withDefaultGlobs(argv, defaultGlobs, VALUE_FLAGS),
];

const bunProcess = spawn('bun', command, {
  stdio: 'inherit',
  env: import.meta.env,
});

bunProcess.on('close', (code) => {
  process.exitCode = code ?? 1;
});
