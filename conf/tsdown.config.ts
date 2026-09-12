import { defineConfig } from 'tsdown';

import type { Maybe } from '../src/js/shared/types/core';

export default defineConfig((options) => {
  const isWatchMode = options.watch === true;

  const commonOptions = <const>{
    outDir: '../dist',
    clean: true,
    treeshake: !isWatchMode,
    outputOptions: {
      chunkFileNames: 'shared.mjs',
      codeSplitting: {
        groups: [
          {
            name: 'shared',
            minShareCount: 2,
          },
        ],
      },
    },
  } satisfies typeof options;

  return [
    {
      ...commonOptions,
      dts: !isWatchMode,
      entry: [
        '../src/js/commitlint/index.ts',
        '../src/js/eslint/index.ts',
        '../src/js/markdownlint/index.ts',
        '../src/js/spelling/index.ts',
        '../src/js/spelling/spelling.test.ts',
        '../src/js/stylelint/index.ts',
        '../src/js/vitest/index.ts',
      ],
      deps: {
        neverBundle: [
          '@commitlint/types',
          '@typescript-eslint/utils',
          'vite',
          'vitest',
        ],
      },
    },
    {
      ...commonOptions,
      outDir: `${commonOptions.outDir}/scripts`,
      entry: [
        '../scripts/commitlint.ts',
        '../scripts/eslint.ts',
        '../scripts/markdownlint.ts',
        '../scripts/stylelint.ts',
      ],
      plugins: [
        {
          name: 'rewrite-config-extension',
          renderChunk: (code, chunk): Maybe<string> => (chunk.name === 'eslint'
            ? code.replaceAll('eslint.config.ts', 'eslint.config.mjs')
            : undefined),
        },
      ],
    },
  ];
});
