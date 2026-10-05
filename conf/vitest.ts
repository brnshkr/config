import { getConfig } from '#vitest/index.ts';

export default getConfig({
  test: {
    maxWorkers: 6,
  },
});
