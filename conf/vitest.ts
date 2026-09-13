import { getConfig } from '../src/js/vitest';

export default getConfig({
  test: {
    maxWorkers: 4,
  },
});
