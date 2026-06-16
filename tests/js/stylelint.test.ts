import { test } from 'vitest';

import { snapshotConfigs } from './utils/config-snapshot';

test('expected stylelint config', () => {
  snapshotConfigs({
    command: (filePath) => `bun stylelint --print-config ${filePath}`,
    fixturesDirectory: `${process.cwd()}/tests/js/fixtures/stylelint`,
  });
});
