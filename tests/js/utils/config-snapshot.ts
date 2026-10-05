import { Minimatch } from 'minimatch';
import { expect } from 'vitest';

import { objectFromEntries, objectKeys } from '#shared/utils/object.ts';
import { traverseDirectory } from '#tests/utils/filesystem.ts';
import { computeConfigDiff } from '#tests/utils/json-diff.ts';

import type { JsonObject } from '#tests/utils/json-diff.ts';

interface SnapshotConfigsOptions {
  fixturesDirectory: string;
  globs?: string[];
  virtualFiles?: string[];
  resolve: (filePath: string) => Promise<JsonObject> | JsonObject;
  normalize?: (config: JsonObject) => JsonObject;
}

const listFixtures = (fixturesDirectory: string): string[] => {
  const filePaths: string[] = [];

  traverseDirectory(fixturesDirectory, (filePath) => {
    filePaths.push(filePath);
  });

  return filePaths;
};

const stripRoot = (config: JsonObject): JsonObject => {
  const root = JSON.stringify(process.cwd()).slice(1, -1);

  return <JsonObject>JSON.parse(JSON.stringify(config).replaceAll(root, '<root>'));
};

const expectEveryGlobCovered = (globs: string[], names: string[]): void => {
  expect(globs.length).toBeGreaterThan(0);

  const uncovered = globs.filter((glob) => names.every((name) => !new Minimatch(glob, { dot: true }).match(name)));

  expect(uncovered).toStrictEqual([]);
};

export const snapshotConfigs = async (options: SnapshotConfigsOptions): Promise<void> => {
  const {
    fixturesDirectory,
    globs,
    virtualFiles = [],
    resolve,
    normalize,
  } = options;

  const configs = new Map(await Promise.all(
    [
      ...listFixtures(fixturesDirectory),
      ...virtualFiles.map((virtualFile) => `${fixturesDirectory}/${virtualFile}`),
    ].map(async (filePath): Promise<[string, JsonObject]> => {
      const config = await resolve(filePath);

      return [
        filePath.replace(`${fixturesDirectory}/`, ''),
        stripRoot(normalize ? normalize(config) : config),
      ];
    }),
  ));

  if (globs !== undefined) {
    expectEveryGlobCovered(globs, objectKeys(configs));
  }

  const groups = new Map<string, string[]>();
  const hashToRepresentative = new Map<string, string>();

  for (const [name, config] of configs) {
    const hash = JSON.stringify(config);
    const representative = hashToRepresentative.get(hash);

    if (representative === undefined) {
      hashToRepresentative.set(hash, name);
      groups.set(name, [name]);
    } else {
      groups.get(representative)?.push(name);
    }
  }

  expect(objectFromEntries(groups)).toMatchSnapshot('config-groups');

  const [baseName, ...otherNames] = objectKeys(groups);

  if (baseName === undefined) {
    return;
  }

  const baseConfig = configs.get(baseName) ?? {};

  expect(baseConfig).toMatchSnapshot(`base: ${baseName}`);

  for (const name of otherNames) {
    expect(computeConfigDiff(baseConfig, configs.get(name) ?? {})).toMatchSnapshot(`diff: ${name}`);
  }
};
