export const withDefaultGlobs = (
  argv: string[],
  defaultGlobs: readonly string[],
  valueFlags: readonly string[] = [],
): string[] => {
  const hasGlob = argv.some((argument, index) => !argument.startsWith('-')
    && !valueFlags.includes(argv[index - 1] ?? ''));

  return [
    ...argv,
    ...hasGlob ? [] : defaultGlobs,
  ];
};
