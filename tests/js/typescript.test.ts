import { expect, test } from 'vitest';

import { run } from '#tests/utils/command.ts';

test('expected typescript config', () => {
  expect(run('bun tsc --showConfig --project conf/tsconfig.json').replaceAll('./conf/', './')).toMatchSnapshot();
});
