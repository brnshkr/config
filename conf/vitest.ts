import { getConfig } from '#vitest/index.ts';

export default getConfig({
  test: {
    maxWorkers: 6,
    testTimeout: 30_000,
  },
});
