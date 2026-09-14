import path from 'node:path';

import { ESLint } from 'eslint';
import { test } from 'vitest';

import { getConfig } from '../../src/js/eslint';

import { snapshotConfigs } from './utils/config-snapshot';

import type { JsonObject } from './utils/json-diff';

const FIXTURES_DIRECTORY = path.join(process.cwd(), 'tests/js/fixtures/eslint');

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
    virtualGlobs: [
      '**/*.md/**',
      '**/*.md/*.js',
      '**/*.jsdoc-defaults',
      '**/*.jsdoc-params',
      '**/*.jsdoc-properties',
    ],
    resolve: async (filePath) => <JsonObject>(
      await eslint.calculateConfigForFile(filePath.replace(FIXTURES_DIRECTORY, () => process.cwd()))
    ),
    normalize: (config) => <JsonObject>JSON.parse(
      JSON.stringify(config).replaceAll(/"brnshkr:brnshkr@[^"]*"/gv, '"brnshkr:brnshkr@<version>"'),
    ),
  });
});
