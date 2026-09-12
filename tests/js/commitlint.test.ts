import { expect, test } from 'vitest';

import { run } from './utils/command';

test('expected commitlint config', () => {
  expect(run(
    'bun --bun x commitlint --config conf/commitlint.mjs --print-config --color false',
  )).toMatchSnapshot();
});
