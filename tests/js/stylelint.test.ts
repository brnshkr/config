import path from 'node:path';

import stylelint from 'stylelint';
import { test } from 'vitest';

import { getConfig } from '#stylelint/index.ts';
import { snapshotConfigs } from '#tests/utils/config-snapshot.ts';

import type { JsonObject } from '#tests/utils/json-diff.ts';

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
