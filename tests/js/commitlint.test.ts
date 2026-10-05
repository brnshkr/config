import { inspect } from 'node:util';

import load from '@commitlint/load';
import { expect, test } from 'vitest';

import { getConfig } from '#commitlint/index.ts';

test('expected commitlint config', async () => {
  const config = await load(getConfig());

  expect(`${inspect(config, { depth: Infinity }).replaceAll(process.cwd(), '.')}\n`).toMatchSnapshot();
});
