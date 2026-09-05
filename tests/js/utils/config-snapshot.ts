import { Minimatch } from 'minimatch';
import { expect } from 'vitest';

import { traverseDirectory } from './filesystem';
import { computeConfigDiff } from './json-diff';

import type { JsonObject } from './json-diff';

interface SnapshotConfigsOptions {
  fixturesDirectory: string;
  globs?: string[];
  virtualGlobs?: string[];
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

const stripRoot = (config: JsonObject): JsonObject => <JsonObject>JSON.parse(
  JSON.stringify(config).replaceAll(JSON.stringify(process.cwd()).slice(1, -1), '<root>'),
);

const expectEveryGlobCovered = (globs: string[], virtualGlobs: string[], names: string[]): void => {
  expect(globs.length).toBeGreaterThan(0);

  const uncovered = globs
    .filter((glob) => !virtualGlobs.includes(glob))
    .filter((glob) => !names.some((name) => new Minimatch(glob, { dot: true }).match(name)));

  expect(uncovered).toStrictEqual([]);
};

export const snapshotConfigs = async (options: SnapshotConfigsOptions): Promise<void> => {
  const {
    fixturesDirectory,
    globs,
    virtualGlobs = [],
    resolve,
    normalize,
  } = options;

  const configs = new Map(await Promise.all(
    listFixtures(fixturesDirectory).map(async (filePath): Promise<[string, JsonObject]> => {
      const config = await resolve(filePath);

      return [
        filePath.replace(`${fixturesDirectory}/`, ''),
        stripRoot(normalize ? normalize(config) : config),
      ];
    }),
  ));

  if (globs !== undefined) {
    expectEveryGlobCovered(globs, virtualGlobs, [...configs.keys()]);
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

  expect(Object.fromEntries(groups)).toMatchSnapshot('config-groups');

  const [baseName, ...otherNames] = [...groups.keys()];

  if (baseName === undefined) {
    return;
  }

  const baseConfig = configs.get(baseName) ?? {};

  expect(baseConfig).toMatchSnapshot(`base: ${baseName}`);

  for (const name of otherNames) {
    expect(computeConfigDiff(baseConfig, configs.get(name) ?? {})).toMatchSnapshot(`diff: ${name}`);
  }
};
