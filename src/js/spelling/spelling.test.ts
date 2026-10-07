import { expect, test } from 'vitest';

import { scan } from '#spelling/index.ts';

test('every tracked file is written in american english', () => {
  const findings = scan().map(({
    path,
    line,
    word,
    suggestion,
  }) => `${path}:${String(line)} — "${word}", use "${suggestion}"`);

  expect(findings).toStrictEqual([]);
});
