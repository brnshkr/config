import { getConfig } from '../src/js/vitest';

export default getConfig({
  test: {
    testTimeout: 120_000,
  },
});
