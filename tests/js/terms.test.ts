import path from 'node:path';

import { expect, test } from 'vitest';

import { TERMS } from '#shared/utils/terms.ts';
import { readText } from '#tests/utils/filesystem.ts';

const MAKEFILE_PATH = path.resolve(import.meta.dirname, '../../conf/Makefile');

test('the changelog spells each term the way the term list does', () => {
  const namesBlock = /^CHANGELOG_NAMES \?=(?<pairs>(?:.*\\\n)*.*)/mv.exec(readText(MAKEFILE_PATH))?.groups?.['pairs'];

  expect(namesBlock).toBeDefined();

  const changelogNames = new Map(String(namesBlock)
    .split('#', 1)
    .join('')
    .split(/\s+/v)
    .filter((pair) => pair.includes('='))
    .map((pair) => pair.split('='))
    .map(([scope, name]) => <const>[
      scope,
      name?.replaceAll('_', ' '),
    ]));

  expect(changelogNames.size).toBeGreaterThan(TERMS.length);
  expect(TERMS.filter((term) => (changelogNames.get(term.toLowerCase()) ?? term) !== term)).toStrictEqual([]);
});
