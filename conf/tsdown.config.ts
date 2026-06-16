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
    },
  } satisfies typeof options;

  return [
    {
      ...commonOptions,
      dts: !isWatchMode,
      entry: [
        '../src/js/eslint/index.ts',
        '../src/js/stylelint/index.ts',
      ],
      deps: {
        neverBundle: [
          '@typescript-eslint/utils',
        ],
      },
    },
    {
      ...commonOptions,
      outDir: `${commonOptions.outDir}/scripts`,
      entry: [
        '../scripts/eslint.ts',
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
