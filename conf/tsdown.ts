import { defineConfig } from 'tsdown';

export default defineConfig((options) => {
  const isWatchMode = options.watch === true;

  return {
    outDir: '../dist',
    clean: true,
    dts: !isWatchMode,
    treeshake: !isWatchMode,
    entry: [
      '../src/js/commitlint/index.ts',
      '../src/js/eslint/index.ts',
      '../src/js/markdownlint/index.ts',
      '../src/js/spelling/index.ts',
      '../src/js/spelling/spelling.test.ts',
      '../src/js/stylelint/index.ts',
      '../src/js/vitest/index.ts',
    ],
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
    deps: {
      neverBundle: [
        '@commitlint/types',
        '@typescript-eslint/utils',
        'vite',
        'vitest',
      ],
    },
  };
});
