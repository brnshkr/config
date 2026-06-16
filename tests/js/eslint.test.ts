import { test } from 'vitest';

import { snapshotConfigs } from './utils/config-snapshot';

test('expected eslint config', () => {
  snapshotConfigs({
    command: (filePath) => `bun eslint --print-config ${filePath}`,
    fixturesDirectory: `${process.cwd()}/tests/js/fixtures/eslint`,
    // NOTICE: Replace version of builtin plugin to avoid snapshot updates on every release
    normalize: (output) => output.replaceAll(/"brnshkr:brnshkr@.*",/gv, '"brnshkr:brnshkr@<version>",'),
  });
});
