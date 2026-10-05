import path from 'node:path';

import { ESLint } from 'eslint';
import { test } from 'vitest';

import { getConfig } from '#eslint/index.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';
import { createPattern } from '#shared/utils/pattern.ts';
import { snapshotConfigs } from '#tests/utils/config-snapshot.ts';

import type { JsonObject } from '#tests/utils/json-diff.ts';

const FIXTURES_DIRECTORY = path.join(process.cwd(), 'tests/js/fixtures/eslint');
const TIMEOUT = 30_000;

test('expected eslint config', async () => {
  const packageConfigs = await getConfig().toConfigs();

  const eslint = new ESLint({
    overrideConfigFile: true,
    overrideConfig: packageConfigs
      .filter(({ files, ignores }) => files !== undefined || ignores === undefined),
  });

  await snapshotConfigs({
    fixturesDirectory: FIXTURES_DIRECTORY,
    globs: [...new Set(packageConfigs.flatMap(({ files }) => (files ?? []).flat()))]
      .filter((glob) => typeof glob === 'string'),
    virtualFiles: [
      'README.md/0_0.mjs',
      'a.ts/0_a.md/*.js',
      'a.ts/1_dummy.jsdoc-defaults.md/*.js',
      'a.ts/2_dummy.jsdoc-params.md/*.js',
      'a.ts/3_dummy.jsdoc-properties.md/*.js',
    ],
    resolve: async (filePath) => <JsonObject>(
      await eslint.calculateConfigForFile(filePath.replace(FIXTURES_DIRECTORY, () => process.cwd()))
    ),
    normalize: (config) => <JsonObject>JSON.parse(
      JSON.stringify(config).replaceAll(
        createPattern('gv')`"${packageOrganization}:${packageOrganization}@[^"]*"`,
        () => `"${packageOrganization}:${packageOrganization}@<version>"`,
      ),
    ),
  });
}, TIMEOUT);
