import path from 'node:path';

import stylelint from 'stylelint';
import { test } from 'vitest';

import { getConfig } from '../../src/js/stylelint';

import { snapshotConfigs } from './utils/config-snapshot';

import type { JsonObject } from './utils/json-diff';

test('expected stylelint config', async () => {
  const config = getConfig();

  await snapshotConfigs({
    fixturesDirectory: path.join(process.cwd(), 'tests/js/fixtures/stylelint'),
    globs: (config.overrides ?? [])
      .flatMap(({ files }) => (Array.isArray(files) ? files : [files]))
      .filter((glob) => typeof glob === 'string'),
    resolve: async (filePath) => <JsonObject><unknown>(await stylelint.resolveConfig(filePath, {
      config,
    })),
  });
});
