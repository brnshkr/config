import { expect, test } from 'vitest';

import { getConfig } from '#vitest/index.ts';

test('expected vitest config', () => {
  const config = JSON.parse(JSON.stringify(getConfig()).replaceAll(process.cwd(), '<root>'));

  expect(config).toMatchSnapshot();
});
