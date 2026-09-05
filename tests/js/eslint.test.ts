import path from 'node:path';

import { ESLint } from 'eslint';
import { test } from 'vitest';

import eslintConfig from '../../conf/eslint.config';
import { getConfig } from '../../src/js/eslint';

import { snapshotConfigs } from './utils/config-snapshot';

import type { JsonObject } from './utils/json-diff';

test('expected eslint config', async () => {
  const repositoryConfigs = await eslintConfig.toConfigs();
  const packageConfigs = await getConfig().toConfigs();

  const eslint = new ESLint({
    overrideConfigFile: true,
    overrideConfig: repositoryConfigs
      .filter(({ files, ignores }) => files !== undefined || ignores === undefined),
  });

  await snapshotConfigs({
    fixturesDirectory: path.join(process.cwd(), 'tests/js/fixtures/eslint'),
    globs: [...new Set(packageConfigs.flatMap(({ files }) => (files ?? []).flat()))]
      .filter((glob) => typeof glob === 'string'),
    virtualGlobs: [
      '**/*.md/**',
      '**/*.md/*.js',
      '**/*.jsdoc-defaults',
      '**/*.jsdoc-params',
      '**/*.jsdoc-properties',
    ],
    resolve: async (filePath) => <JsonObject>(await eslint.calculateConfigForFile(filePath)),
    normalize: (config) => <JsonObject>JSON.parse(
      JSON.stringify(config).replaceAll(/"brnshkr:brnshkr@[^"]*"/gv, '"brnshkr:brnshkr@<version>"'),
    ),
  });
});
