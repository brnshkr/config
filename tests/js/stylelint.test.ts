import path from 'node:path';

import stylelint from 'stylelint';
import { test } from 'vitest';

import { getConfig } from '../../src/js/stylelint';

import { snapshotConfigs } from './utils/config-snapshot';

import type { JsonObject } from './utils/json-diff';

const CONFIG_FILE = path.join(process.cwd(), 'conf/stylelint.mjs');

test('expected stylelint config', async () => {
  await snapshotConfigs({
    fixturesDirectory: path.join(process.cwd(), 'tests/js/fixtures/stylelint'),
    globs: (getConfig().overrides ?? [])
      .flatMap(({ files }) => (Array.isArray(files) ? files : [files]))
      .filter((glob) => typeof glob === 'string'),
    resolve: async (filePath) => <JsonObject><unknown>(await stylelint.resolveConfig(filePath, {
      configFile: CONFIG_FILE,
    })),
  });
});
