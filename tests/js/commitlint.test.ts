import { expect, test } from 'vitest';

import { run } from './utils/command';

test('expected commitlint config', () => {
  expect(run(`bun commitlint --print-config --color false`)).toMatchSnapshot();
});
