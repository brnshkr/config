import { defineConfig } from 'tsdown';

export default defineConfig((options) => {
  const isWatchMode = options.watch === true;

  const commonOptions = <const>{
    format: 'esm',
    treeshake: !isWatchMode,
    clean: !isWatchMode,
    outputOptions: {
      chunkFileNames: 'shared.mjs',
    },
  } satisfies typeof options;

  return [
    {
      ...commonOptions,
      entry: [
        'src/js/eslint/index.ts',
        'src/js/stylelint/index.ts',
      ],
      dts: !isWatchMode,
    },
    {
      ...commonOptions,
      entry: [
        'scripts/eslint.ts',
        'scripts/stylelint.ts',
      ],
      outDir: 'dist/scripts',
    },
  ];
});
