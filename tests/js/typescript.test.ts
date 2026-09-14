import { expect, test } from 'vitest';

import { run } from './utils/command';

test('expected typescript config', () => {
  expect(run('bun tsc --showConfig --project conf/tsconfig.json')).toMatchSnapshot();
});
